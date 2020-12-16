<?php

require_once '../../vendor/autoload.php';

$api = new CrowdHandler\PublicClient('ace1f8062f2df869a5fb0cbd69f51c10d2821dd1e4519e110206eca9e3db86c8'); // your public key here.
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

	<p>You requested the url <code><?php echo $ch->url ?></code> with the token <code><?php echo $ch->token ?></code><p>

    <?php if($ch->result->status == 2): ?>
        <p>No valid response was received from CrowdHandler</p>
    <?php else: ?>
        <p>CrowdHandler sent this response:</p>
    <?php endif ?>

	<code><pre><?php echo $ch->result ?></pre></code>

    <?php if ($ch->result->promoted): ?>
        <p>This user is <strong>promoted</strong> for this page</p>
    <?php else: ?>
        <p>This user is <strong>not promoted</strong> for this page</p>
        <p>This user will be redirected to: <a href="<?php echo $ch->redirectUrl ?>"><code><?php echo $ch->redirectUrl ?></code></a>
    <?php endif ?>

</body>
<?php

$ch->recordPerformance(200);

?>