(function() {
	
	var doAction = function(action,data,callback) {
		$.ajax({
			url : '?action=' + action,
			type : 'post',
			data : data,
			dataType : 'json',
			success : callback
		});
	};
	
	var startPoker = function(callback) {
		doAction('start_poker',{},function() {
			callback();
		});
	};
	
	var checkLogin = function(callback) {
		doAction('check_login',{},function(logged_in) {
			callback(logged_in);
		});
	};
	
	var checkActive = function(callback) {
		doAction('check_active',{},function(active) {
			callback(active);
		});
	};
	
	var checkOwner = function(callback) {
		doAction('check_owner',{},function(owner) {
			callback(owner);
		});
	};
	
	var showSkip = function(callback) {
		if (!$.isFunction(callback)) callback = function(){};
		$('#skip').fadeIn(callback);
	};
	
	var hideSkip = function(callback) {
		if (!$.isFunction(callback)) callback = function(){};
		$('#skip').fadeOut(callback);
	};
	
	var showLogin = function(callback) {
		if (!$.isFunction(callback)) callback = function(){};
		
		$('#login').fadeIn().find('button').bind('click',function() {
			processLogin(callback);
		});
	};
	
	var hideLogin = function() {
		$('#login').fadeOut();
	};
	
	var processLogin = function(callback) {
		doAction('login',{un : $('#username').val(),pw : $('#password').val()},function(success) {
			hideLogin();
			callback(success);
		});
	};
	
	var showProjects = function() {
		checkProject(function(has_project) {
			if (has_project) {
				showUsers();
				startSession();
			}
			else {
				doAction('get_projects',{},function(projects) {  
					$.each(projects, function(id,project) {
						var p = $('<div/>').addClass('ui-corner-all project').html(project).data('project_id',id);
						p.click(function() {
							$('#projects').fadeOut();
							selectProject($(this).data('project_id'));
						});
						$('#projects').append(p);
					});
					$('#projects').fadeIn();
				});				
			}
		});
	};
	
	var checkProject = function(callback) {
		doAction('check_project',{},function(has_project) {
			callback(has_project);
		});
	};
	
	var selectProject = function(project_id) {
		doAction('select_project',{id : project_id},function() {
			showUsers();
			startSession();
		});
	};
	
	var renderUsers = function(callback) {
		doAction('get_users',{},function(users) {
			$('#users .list').html('');
			$.each(users,function() {
				var user = this;
				var u = $('<div/>').addClass('ui-corner-all user').html(user.name);
				if (user.active) u.addClass('active');
				if (user.owner) u.addClass('owner');
				$('#users .list').append(u);
			});
			if ($.isFunction(callback)) callback();
		});
	};
	
	var showUsers = function() {
		renderUsers(function() {
			$('#users').fadeIn();
			setInterval(renderUsers, 2500);
		});
	};
	
	var renderEstimates = function() {
		var base_tag = $('<div/>').addClass('ui-corner-all estimate');
		doAction('get_estimates',{},function(est) {
			$.each(est,function(estimate,names) {
				$.each(names,function(i,name) {
					var tag = base_tag.clone();
					tag.html(name);
					$('#est' + estimate + ' .estimates').append(tag);
				});
			});
			checkOwner(function(owner) {
				if (owner) {
					$('.card').css('opacity',0.6)
					  .bind('mouseenter',function() {
						$(this).css('opacity',1);
					}).bind('mouseleave',function() {
						$(this).css('opacity',0.6);
					}).click(function() {
						$('.card').unbind('mouseenter mouseleave click');
						doAction('save_estimate',{ estimate : $(this).attr('id').replace('est','') },function() {
							renderStory();
						});
					});
				}
			});
		});
	};
	
	var estimate_timer = false;
	var checkEstimates = function()
	{
		doAction('check_estimates',{},function(est) {
			$('#story h2 span.total').html(est.users);
			$('#story h2 span.got').html(est.estimates);
			if (est.users == est.estimates) {
				$('#cards .card').addClass('fixed');
				$('#cards .card').die('click');
				clearTimeout(estimate_timer);
				renderEstimates();
			}
		});
	};
	
	var renderStory = function() {
		$('#story').hide();
		$('#cards').hide();
		$('.estimates').html('');
		$('#cards .card').removeClass('fixed selected');
		$('#cards .card').live('click',function() {
			$('#cards .card').removeClass('selected');
			$(this).addClass('selected');
			doAction('my_estimate',{estimate : $(this).attr('id').replace('est','')});
		});
		doAction('get_story',{},function(story) {
			estimate_timer = setInterval(checkEstimates,1500);
			$('#story h1').html(story.name);
			$('#story p').html(story.description);
			$('#story').fadeIn();
			$('#cards').fadeIn();
			checkOwner(function(owner) {
				if (owner) {
					$('#skip').fadeIn();
				}
			});
		});
	};
	
	var waitUntilActive = function(callback) {
		
		var checkIt = function() {
			checkActive(function(active) {
				if (!active) {
					setTimeout(checkIt,2000);
				} 
				else {
					callback();
				}
			});
		};
		
		setTimeout(checkIt,2000);
	};
	
	var startSession = function() {
		checkActive(function(active) {
			if (active) {
				renderStory();
			}
			else {
				checkOwner(function(owner) {
					if (owner) {
						$('#start').fadeIn().click(function() {
							$('#start').fadeOut();
							startPoker(function() {
								renderStory();
							});
						});
					}
					else {
						$('#wait').fadeIn();
						waitUntilActive(function() {
							$('#wait').fadeOut();
							renderStory();
						});
					}
				});
			}
		});
	};
	
	$(function() {
		checkLogin(function(logged_in) {
			if (logged_in) {
				showProjects();
			}
			else {
				showLogin(function(logged_in) {
					if (logged_in) showProjects();
				});
			}
		});
	});
	
}());