<?php
require_once('../../server/bootstrap.php');

$smarty->assign('admin', false);
$smarty->assign('practice', true);

$show_intro = true;

try {
    $r = new Request(array(
        'auth_token' => array_key_exists('ouat', $_REQUEST) ? $_REQUEST['ouat'] : null,
        'contest_alias' => $_REQUEST['contest_alias'],
    ));
    $show_intro = ContestController::showContestIntro($r);
} catch (Exception $e) {
    header('HTTP/1.1 404 Not Found');
    die(file_get_contents('../404.html'));
}

if ($show_intro) {
    $smarty->display('../../templates/arena.contest.intro.tpl');
} else {
    $smarty->assign('bodyid', 'practice');
    $smarty->assign('jsfile', '/ux/contest.js');
    $smarty->display('../../templates/arena.contest.tpl');
}
