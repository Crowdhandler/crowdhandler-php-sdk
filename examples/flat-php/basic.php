<?php

require_once '../../vendor/autoload.php';

$api = new CrowdHandler\PublicClient('de50382842c3f4928adbc8ed9ab0518c27c883913fa790e163e0596f4b6445ed'); // your public key here.
$ch = new CrowdHandler\GateKeeper($api);
$ch->checkRequest();
$ch->redirectIfNotPromoted();
$ch->setCookie();

?>
<html>
<head>
    <title>Crowdhandler PHP Integration</title>
</head>
<body>

	<h1>CrowdHandler PHP Integration</h1>

	<p>You requested the url <code><?=$ch->url ?></code> with the token <code><?=$ch->token ?></code><p>

    <?php if($ch->result->status == 2): ?>
        <p>No valid response was received from CrowdHandler</p>
    <?php else: ?>
        <p>CrowdHandler sent this response:</p>
    <?php endif ?>

	<code><pre><?=$ch->result ?></pre></code>

    <?php if ($ch->result->promoted): ?>
        <p>This user is <strong>promoted</strong> for this page</p>
    <?php else: ?>
        <p>This user is <strong>not promoted</strong> for this page</p>
        <p>This user will be redirected to: <a href="<?=$ch->redirectUrl ?>"><code><?=$ch->redirectUrl ?></code></a>
    <?php endif ?>

</body>
<?php

$ch->recordPerformance(200);

?>