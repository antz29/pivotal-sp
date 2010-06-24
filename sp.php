<?php
class SP {
	
	private $_user;
	private $_token;
	private $_project;
	
	private $_key;
	private $_data;
	
	public function __construct($action)
	{
		session_start();		
		$method = "action_{$action}";
		if (!method_exists($this,$method)) return;

		$this->_user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
		$this->_token = isset($_SESSION['token']) ? $_SESSION['token'] : null;
		$this->_project = isset($_SESSION['project']) ? $_SESSION['project'] : null;
		
		$this->initSession();
		
		if ($this->_key) {
			$this->_data = apc_fetch($this->_key);
		}
		
		$this->_data['users'][$this->_user] = time();
		
		die(json_encode($this->$method()));
	}
	
	public function __destruct()
	{
		if ($this->_key) {
			apc_store($this->_key,$this->_data);
		}
	}
	
	private function action_check_login()
	{
		return isset($_SESSION['token']);	
	}

	private function action_check_project()
	{
		return isset($_SESSION['project']);	
	}
	
	private function action_check_active()
	{
		return isset($this->_data['active']) ? $this->_data['active'] : false;	
	}
	
	private function action_check_owner()
	{
		foreach ($this->_data['members'] as $member) {
			if ($this->_user == $member['email']) return $member['owner'];
		}	
		return false;
	}
	
	private function action_get_estimates()
	{
		$out = array();
		foreach ($this->_data['estimates'] as $email => $est) {
			$out[$est][] = $this->_data['members'][$email]['name'];
		}
		return $out;	
	}
	
	private function action_check_estimates()
	{
		return array('users' => count($this->_data['users']),'estimates' => count($this->_data['estimates']));
	}
	
	private function action_get_projects()
	{
		$xml = simplexml_load_string($this->sendRequest('http://www.pivotaltracker.com/services/v3/projects'));
		$projs = array();
		foreach ($xml->project as $project) {
			$projs[(int) $project->id] = (string) $project->name; 
		}
		return $projs;
	}
	
	private function action_select_project()
	{
		$_SESSION['project'] = $_POST['id'];
		$this->_project = $_POST['id'];
		$this->initSession();
		return;
	}
	
	private function getStories()
	{
		$iterations[] = simplexml_load_string($this->sendRequest("http://www.pivotaltracker.com/services/v3/projects/{$this->_project}/iterations/current"));
		$iterations[] = simplexml_load_string($this->sendRequest("http://www.pivotaltracker.com/services/v3/projects/{$this->_project}/iterations/backlog?limit=1"));

		$stories = array();
		foreach ($iterations as $iteration) {
			foreach ($iteration->iteration->stories->story as $story) {
				if ($story->story_type != 'feature') continue;
				if ($story->current_state != 'unstarted') continue;
				//if ((int) $story->estimate > 0) continue;
				
				$stories[] = array(
					'id' => (int) $story->id,
					'name' => (string) $story->name,
					'description' => (string) $story->description
				);
			}
		}
		
		return $stories;
	}
	
	private function action_start_poker()
	{
		if (!$this->action_check_owner()) return;
		
		$this->_data['stories'] = $this->getStories();
		$this->_data['story'] = array_shift($this->_data['stories']); 
		$this->_data['estimates'] = array();
		$this->_data['active'] = true;
		
		return $this->_data['story'];
	}
	
	private function action_skip_story()
	{
		if (!$this->action_check_owner()) return;
		
		$this->_data['estimates'] = array();
		array_push($this->_data['stories'],$this->_data['story']);
		$this->_data['story'] = array_shift($this->_data['stories']); 
	}
	
	private function action_get_story()
	{
		return isset($this->_data['story']) ? $this->_data['story'] : false; 
	}
	
	private function action_save_estimate()
	{
		if (!$this->action_check_owner()) return;
		
		$cnf['http']['method'] = 'PUT';
		$cnf['http']['timeout'] = 5; 
		
		$headers[] = "X-TrackerToken: {$this->_token}";
		$headers[] = "Content-type: application/xml";		
		$cnf['http']['header'] = implode("\r\n",$headers)."\r\n";
		
		$cnf['http']['content'] = "<story><estimate>{$_POST['estimate']}</estimate></story>";
		
		$context = stream_context_create($cnf);
		
		file_get_contents("http://www.pivotaltracker.com/services/v3/projects/{$this->_project}/stories/{$this->_data['story']['id']}",null,$context);
		
		$this->_data['story'] = array_shift($this->_data['stories']); 
		$this->_data['estimates'] = array();
	}
	
	private function action_my_estimate()
	{
		$this->_data['estimates'][$this->_user] = $_POST['estimate']; 
	}
	
	private function action_login()
	{
		if (!isset($_POST['un']) || !isset($_POST['pw'])) return false;
		$xml = simplexml_load_string($this->sendRequest('https://www.pivotaltracker.com/services/v3/tokens/active',null,null,$_POST['un'],$_POST['pw']));
		$_SESSION['user'] = $_POST['un'];
		$_SESSION['token'] = (string) $xml->guid;
		return true;
	}
	
	private function sortUsers($a,$b)
	{
		return  ($b['owner'] + ($b['active'] * 2)) - ($a['owner'] + ($a['active'] * 2)); 	
	}
	
	private function action_get_users()
	{
		if (!isset($this->_data['members'])) {
			$xml = simplexml_load_string($this->sendRequest("http://www.pivotaltracker.com/services/v3/projects/{$this->_project}/memberships"));
			$users = array();
			foreach ($xml->membership as $user) {
				$users[(string) $user->person->email] = array(
					'name' => (string) $user->person->name,
					'email' => (string) $user->person->email,
					'owner' => ($user->role == 'Owner'),
				);
			}
			$this->_data['members'] = $users;
		}
		
		foreach ($this->_data['members'] as &$user) {
			$user['active'] = isset($this->_data['users'][$user['email']]);
		}
		
		return $this->_data['members'];
	}
	
	private function action_logout()
	{
		unset($_SESSION['token']);
		return true;
	}
	
	private function initSession()
	{
		if (!$this->_project) return;
		$this->_key = md5($this->_project.__FILE__);
		apc_add($prj,array());
	}
	
	private function sendRequest($url,$data=null,$method=null,$un=null,$pw=null)
	{
		$cnf['http']['method'] = isset($method) ? $method : 'GET';
		$cnf['http']['timeout'] = 5; 
		
		$headers = array();
		if ((!isset($un) || !isset($pw)) && isset($this->_token)) {
			$headers[] = "X-TrackerToken: {$this->_token}";
		}
		elseif(isset($un) && isset($pw)) {
			$auth = base64_encode($un.':'.$pw);
			$headers[] = "Authorization: Basic {$auth}";
		}
		else {
			return $this->error('Please provide your pivotal tracker username and password to login');
		}
		
		$headers[] = "Content-type: application/x-www-form-urlencoded";
		
		$cnf['http']['header'] = implode("\r\n",$headers)."\r\n";
		
		if (isset($data)) {
			$cnf['http']['content'] = http_build_query($data);
		}
				
		$context = stream_context_create($cnf);

		return file_get_contents($url, false, $context); 
	}
	
	private function error($msg)
	{
		return $msg;
	}
}
