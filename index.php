<?php
/**
 * @Project: RollBugServer
 * @User   : mira
 * @Date   : 21.11.18
 * @Time   : 17:34
 */

use catchbug\helper;
use catchbug\user;
use catchbug\config;

require_once __DIR__ . '/vendor/autoload.php';

session_start();

/**
 *
 * @var $ajax_token string ajax security token
 */
if(!isset($_SESSION['ajax_token'])) {
  try {
    $ajax_token = md5(random_bytes(255));
  } catch (Exception $e) {
  }
  $_SESSION['ajax_token'] = $ajax_token;
} else {
  $ajax_token = $_SESSION['ajax_token'];
}

$content = '';
$modalWindow = '';
$javascriptContent = '';
$title = '';

$section = strtok($_SERVER['QUERY_STRING'], '/');
$helper = new helper();
try {
  $config = new config(true);
} catch (Exception $e) {
  die($e->getMessage());
}

// user is logged
if (isset($_SESSION['user_id'])){

  require_once __DIR__ . '/inc/mysqli.php';

  $user = new user($_SESSION['user_id'], $mysqli);

  $projectId = null;

  $userSection = '';
  switch ($section){
    case 'system':
      include __DIR__ . '/inc/section_system_settings.php';
      break;
    case 'user':
      $userSection = strtok('/');
      switch ($userSection){
        case 'welcome':
          include __DIR__ . '/inc/section_user_welcome.php';
          break;

        case 'settings':
          include __DIR__ . '/inc/section_user_settings.php';
          break;

        case 'newproject':
          include __DIR__ . '/inc/section_user_newproject.php';
          break;
      }

      break; // end section user

    case 'project':
      $projectId = (integer) strtok('/');

      $projectSection = strtok('/');
      $projectSection = $projectSection === false ? 'items' : $projectSection;

      if (($projectId === 0) || $user->isProject($projectId)) {

        $projectName = $projectId !== 0 ? $user->getProject($projectId)->getName() : 'All projects';
        $projectDescription = $projectId !== 0 ? $user->getProject($projectId)->getDescription() : '';

        // project top menu
        $projectTomMenuSettings = $projectId !== 0 ? <<<HTML
  <li class="nav-item {$helper->checkActive($projectSection, 'settings')}">
    <a class="nav-link" href="{$config->rewrite}project/$projectId/settings">Settings</a>
  </li> 
HTML
            : '';

        $content .= <<<HTML
<h1>{$projectName}</h1>
<h4 style="margin-top: -0.5rem;"><small class="text-muted"><em>{$projectDescription}</em></small></h4>

<ul class="nav justify-content-center subnav">
  <li class="nav-item {$helper->checkActive($projectSection, 'items')}">
    <a class="nav-link" href="{$config->rewrite}project/$projectId/items">Items</a>
  </li>
  $projectTomMenuSettings
<div class="pt-4"></div>
</ul>
<hr>
HTML;

        switch ($projectSection) {
          // items list
          case 'items':
            include __DIR__ . '/inc/section_project_items.php';
            break;

          // item
          case 'item':
            $projectItem = (integer)strtok('/');
            $itemSection = strtok('/');
            if ($itemSection === false) {
              include __DIR__ . '/inc/section_project_item.php';
            } else {
              switch ($itemSection) {
                // occurrence
                case 'occurrence':
                  $itemOccurrenceId = (integer)strtok('/');
                  if ($itemOccurrenceId > 0) {
                    include __DIR__ . '/inc/section_project_item_occurrence.php';
                  }
                  break;
              }
            }
            break;

          // peoject settings
          case 'settings':
            $content .= include __DIR__ . '/inc/section_project_settings.php';
            break;

        } // end switch $projectSection

      } else {
        $content .= '<h4 class="text-danger">Project not found</h4>';
      }
      break; // section project

  }  // end switch section

// user NOT logged
} else {
  // read default index file for all web visitors
  $content = include __DIR__ . '/index_default.php';
}




echo <<<HTML
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/devicon.css">
    <link rel="stylesheet" href="/css/devicon-colors.css">
    <link rel="stylesheet" href="/css/c3.min.css">
    <link rel="stylesheet" href="/css/custom.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

    <title>$title</title>

  </head>
  <body>
  
<div id="errorModal" class="modal fade">
	<div class="modal-dialog modal-confirm">
		<div class="modal-content">
			<div class="modal-header justify-content-center">
				<div class="icon-box">
					<i class="material-icons">&#xE5CD;</i>
				</div>				
				<h4 class="modal-title">Sorry!</h4>	
			</div>
			<div class="modal-body">
				<p class="text-center" id="errorModalText">Your transaction has failed. Please go back and try again.</p>
			</div>
			<div class="modal-footer">
				<button class="btn btn-danger btn-block" data-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>

<div id="okModal" class="modal fade">
	<div class="modal-dialog modal-confirm modal-confirm-ok">
		<div class="modal-content">
			<div class="modal-header justify-content-center">
				<div class="icon-box">
					<i class="material-icons">&#xE876;</i>
				</div>				
				<h4 class="modal-title">OK!</h4>	
			</div>
			<div class="modal-body">
				<p class="text-center" id="okModalText"></p>
			</div>
			<div class="modal-footer">
				<button class="btn btn-success btn-block" data-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>
HTML;
// ----------------------------------- navbar
echo <<<HTML
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
      <a class="navbar-brand align-self-stretch" href="/">catchBug <small><small class="text-muted">v{$config->version}</small></small></a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarCollapse">
HTML;

if (isset($_SESSION['user_id'])) {
  echo <<<HTML
        <ul class="navbar-nav mr-auto">
          <li class="nav-item {$helper->checkActive($section, 'dashboard')}">
            <a class="nav-link" href="{$config->rewrite}dashboard">Dashboard</a>
          </li>
          <li class="nav-item dropdown {$helper->checkActive($section, 'project')}{$helper->checkActive($userSection, 'newproject')}">
            <a class="nav-link dropdown-toggle" href="#" id="navbarProjects" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Projects</a>
            <div class="dropdown-menu" aria-labelledby="navbarProjects">
              <a class="dropdown-item {$helper->checkActive($projectId, 0)}" href="{$config->rewrite}project/">All projects</a>

HTML;

  /** @var \catchbug\project $project */
  foreach ($user->projects as $project){
    echo <<<HTML
<a class="dropdown-item {$helper->checkActive($projectId, $project->getId())}" href="{$config->rewrite}project/{$project->getId()}/items" data-id="{$project->getId()}">{$project->getName()}</a>
HTML;

  }

  echo <<<HTML
              <div class="dropdown-divider"></div>
              <a class="dropdown-item {$helper->checkActive($userSection, 'newproject')}" id="navNewProject" href="{$config->rewrite}user/newproject">New project</a>
            </div>
          </li>
        </ul>
HTML;
}

// system settings, user menu and logout
if (isset($_SESSION['user_id'])){

  $systemSettingsLink = $user->root ? <<<HTML
  <li class="nav-item">
    <a class="nav-link{$helper->checkActive($section, 'system')}" href="{$config->rewrite}system/" id="navSystem">System</a>
  </li>
HTML
      : '';


  echo <<<HTML
<ul class="nav navbar-nav ml-auto">
  $systemSettingsLink
  <li class="nav-item">
    <a class="nav-link{$helper->checkActive($section, 'user')}" href="{$config->rewrite}user/settings" id="navUser">
    <img src="{$user->getGravatarImgLink()}" class="avatar" alt="">
{$user->name}</a>
  </li>
  <li class="nav-item pl-3">
    <a class="nav-link" href="/logout.php" id="navLogout">Log out</a>
  </li>
</ul>
HTML;

} else {
  // user login and register forms
  echo <<<HTML
<ul class="nav navbar-nav navbar-right ml-auto">			
			<li class="nav-item">
				<a data-toggle="dropdown" class="nav-link dropdown-toggle" href="#">Login</a>
				<ul class="dropdown-menu form-wrapper">					
					<li>
						<form id="formLogin">
							<div class="form-group">
								<input type="text" name="username" class="form-control" placeholder="Username" required="required">
							</div>
							<div class="form-group">
								<input type="password" name="password" class="form-control" placeholder="Password" required="required">
							</div>
							<input type="submit" class="btn btn-primary btn-block" value="Login">
							<div class="form-footer">
								<a href="#">Forgot Your password?</a>
							</div>
							
							<div class="or-seperator"><b>or</b></div>					
						
							<p class="hint-text">Sign in with your social media account</p>
							<div class="form-group social-btn clearfix">
								<a href="#" class="btn btn-primary pull-left"><i class="fa fa-facebook"></i> Facebook</a>
								<a href="#" class="btn btn-info pull-right"><i class="fa fa-twitter"></i> Twitter</a>
							</div>
							
						</form>
					</li>
				</ul>
			</li>
			<li class="nav-item">
				<a href="#" data-toggle="dropdown" class="btn btn-primary dropdown-toggle get-started-btn mt-1 mb-1">Sign up</a>
				<ul class="dropdown-menu form-wrapper">					
					<li>
						<form id="formRegister">
							<p class="hint-text">Fill in this form to create your account!</p>
							<div class="form-group">
								<input type="text" name="username" class="form-control" placeholder="Username" data-rule-required="true" data-msg-required="Please enter your name">
							</div>
							<div class="form-group">
								<input type="password" name="password" id="password" class="form-control" placeholder="Password" data-rule-required="true" data-msg-required="Please enter password" data-rule-minlength="5" data-msg-minlength="Min 5 char">
							</div>
							<div class="form-group">
								<input type="password" name="passwordConfirm" class="form-control" placeholder="Confirm Password" data-rule-required="true" data-msg-required="Please confirm password" data-rule-equalTo="#password" data-msg-equalTo="Passwords not match">
							</div>
							<div class="form-check">
								<input type="checkbox" name="consent" class="form-check-input" id="cbConsent" data-rule-required="true" data-msg-required="Please consent.">
								<label class="checkbox-inline" for="cbConsent"> I accept the <a href="#">Terms &amp; Conditions</a></label>
							</div>
							<input type="submit" class="btn btn-primary btn-block" value="Sign up">
						</form>
					</li>
				</ul>
			</li>
		</ul>
HTML;
}

echo <<<HTML
       </div>
    </nav>
HTML;
// ----------------------------------- content

echo '<main role="main" class="container" id="content">' . $content . '</main>';

echo <<<HTML
<footer class="container-fluid pb-5">
<dv class="row">

</dv>
</footer>
$modalWindow
<div id="loading" style="display: none;"></div>
</body>

<script type="text/javascript">
  var AJAX_TOKEN = '$_SESSION[ajax_token]',
  _REWRITE = '{$config->rewrite}';
</script>

<script src="/js/jquery-3.3.1.min.js"></script>
<script src="/js/bootstrap.bundle.min.js"></script>
<script src="/js/d3.min.js" charset="utf-8"></script>
<script src="/js/c3.min.js"></script>
<script src="/js/bootstrap-notify.js"></script>
<script src="/js/jquery.validate.min.js"></script>

<script src="/js/index.js"></script>
<script type="text/javascript">$javascriptContent</script>
</html>
HTML;

