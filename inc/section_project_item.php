<?php
/**
 * @Project: RollbarServer
 * @User   : mira
 * @Date   : 22.11.18
 * @Time   : 19:42
 */

use rollbug\item;
use rollbug\occurrence;

/** @var \rollbug\user $user */

$query = "SELECT id, project_id, level, language, id_str, type, last_occ, last_timestamp FROM item WHERE user_id=$user->id and project_id=$projectId and id=$projectItem";
if ($result = $mysqli->query($query)) {
  $obj = $result->fetch_object();
  $item  = new item($obj);
  $result->close();

  $query = "SELECT id, project_id, timestamp, data FROM occurence WHERE user_id=$user->id and project_id=$projectId and item_id=$projectItem and user_id=$user->id order by id desc";
  if ($result = $mysqli->query($query)) {
    while ($obj = $result->fetch_object()){
      $item->occurrences[$obj->id] = new occurrence($obj);
    }
  }
}
/*
echo '<pre>';
var_dump($item);
echo '</pre>';
*/

/**
 * Helper function for tab name in heredoc
 *
 * @param $type
 *
 * @return string
 */
$tabTracebackName = function ($type){
  switch ($type){
    case 'trace':
      return 'Traceback';
      break;

    case 'message':
      return 'Message';
      break;

    case 'crash_report':
      return 'Crash Report';
      break;
  }
  return '';
};

$tabTracebackContent = <<<HTML
<p>{$item->exceptionClass}: {$item->exceptionMessage}</p>
HTML;

if ($item->type === 'trace'){
  $tabTracebackContent .= <<<HTML
<p>File: {$item->file} line: {$item->line}</p>
HTML;

}


$content .= <<<HTML
<h4>#{$item->id} {$item->exceptionClass}: {$item->exceptionMessage}</h4>
<hr>
<form class="form-inline">
<label class="my-1 mr-2" for="selectLevel">Level:</label>
<select class="custom-select custom-select-sm my-1 mr-sm-2" id="selectLevel">
<option value="critical" {$active->checkSelected('ctitical', $item->level)}>Critical</option>
<option value="error" {$active->checkSelected('error', $item->level)}>Error</option>
<option value="warning" {$active->checkSelected('warning', $item->level)}>Warning</option>
<option value="info" {$active->checkSelected('info', $item->level)}>Info</option>
<option value="debug" {$active->checkSelected('debug', $item->level)}>Debug</option>
</select>
</form>

<hr>

<div class="d-flex flex-row mb-3">
  <div class="p-2">First seen: {$item->getFirstTimestampStr('d.m.Y H:i:s', $user->DateTimeZone)}</div>
  <div class="p-2">Last seen: {$item->getLastTimestampStr('d.m.Y H:i:s', $user->DateTimeZone)}</div>
  <div class="p-2">Occurrences: {$item->lastOcc}</div>
</div>

<hr>

<nav>
  <div class="nav nav-tabs" id="nav-tab" role="tablist">
    <a class="nav-item nav-link active" id="nav-traceback-tab" data-toggle="tab" href="#nav-traceback" role="tab" aria-controls="nav-traceback" aria-selected="true">{$tabTracebackName($item->type)}</a>
    <a class="nav-item nav-link" id="nav-occurrences-tab" data-toggle="tab" href="#nav-occurrences" role="tab" aria-controls="nav-occurrences" aria-selected="false">Occurrences</a>
    <a class="nav-item nav-link" id="nav-browser-tab" data-toggle="tab" href="#nav-browser" role="tab" aria-controls="nav-browser" aria-selected="false">Browser/OS</a>
    <a class="nav-item nav-link" id="nav-ipaddr-tab" data-toggle="tab" href="#nav-ipaddr" role="tab" aria-controls="nav-ipaddr" aria-selected="false">IP Adresses</a>
  </div>
</nav>

<div class="tab-content" id="nav-tabContent">
  <div class="tab-pane fade pt-3 show active" id="nav-traceback" role="tabpanel" aria-labelledby="nav-traceback-tab">
  $tabTracebackContent
  </div>
  <div class="tab-pane fade pt-3" id="nav-occurrences" role="tabpanel" aria-labelledby="nav-occurrences-tab">
  bbbbb...occurrences
  </div>
  <div class="tab-pane fade pt-3" id="nav-browser" role="tabpanel" aria-labelledby="nav-browser-tab">
  cccc...browser
  </div>
  <div class="tab-pane fade pt-3" id="nav-ipaddr" role="tabpanel" aria-labelledby="nav-ipaddr-tab">
  cccc...ipaddr
  </div>
</div>
HTML;
