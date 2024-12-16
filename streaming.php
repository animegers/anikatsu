<?php
require ('./_config.php');
session_start();
$parts = parse_url($_SERVER['REQUEST_URI']);
parse_str($parts['query'], $queryParams); // Parse the query string to get episode parameter
$page_url = explode('/', $parts['path']);
$url = $page_url[count($page_url) - 1];
$animeID = explode('-episode-', $url) [0];
$slug = explode('-', $animeID);
$dub = (end($slug) == 'dub') ? "dub" : "sub";
// Create $epurl combining anime ID and episode parameter
$requestedEpisodeId = $queryParams['ep']??null; // Get the 'ep' parameter
$epurl = $requestedEpisodeId ? "$animeID?ep=$requestedEpisodeId" : $animeID;
$getAnime = file_get_contents("$api/api/v2/hianime/anime/$animeID");
$getAnime = json_decode($getAnime, true);
$getEpisode = file_get_contents("$api/api/v2/hianime/anime/$animeID/episodes");
$getEpisode = json_decode($getEpisode, true);
if (!$getAnime['success'] || !$getEpisode['success']) {
    header('Location: https://www.animegers.com/home');
    exit;
}
$anime = $getAnime['data']['anime']['info'];
$episodeList = $getEpisode['data']['episodes'];
$EPISODE_NUM = "Unknown";
$EPISODEE_ID = null;
if ($requestedEpisodeId) {
    foreach ($episodeList as $episodee) {
        if ($episodee['episodeId'] === $epurl) { // Match $epurl
            $EPISODE_NUM = $episodee['number'];
            $EPISODEE_ID = $episodee['episodeId'];
            break;
        }
    }
}
$ANIME_RELEASED = $anime['aired'];
$ANIME_NAME = $anime['name'];
$ANIME_IMAGE = $anime['poster'];
$ANIME_TYPE = $anime['stats']['type'];
$pageID = $url;
$CurPageURL = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$pageUrl = $CurPageURL;
// Check for page view count
$query = mysqli_query($conn, "SELECT * FROM `pageview` WHERE pageID = '$pageID'");
$rows = mysqli_fetch_array($query);
$counter = $rows['totalview'];
$id = $rows['id'];
if (empty($counter)) {
    $counter = 1;
    mysqli_query($conn, "INSERT INTO `pageview` (pageID, totalview, like_count, dislike_count, animeID) VALUES('$pageID', '$counter', '1', '0', '$animeID')");
    header('Location: ' . $pageUrl); // Redirect to refresh and prevent re-submit
    exit; // Ensure no further execution
    
} else {
    $counter++;
    mysqli_query($conn, "UPDATE `pageview` SET totalview = '$counter' WHERE pageID = '$pageID'");
}
// Get like and dislike counts
$like_count = $rows['like_count'];
$dislike_count = $rows['dislike_count'];
$totalVotes = $like_count + $dislike_count;
// Handle user history
if (isset($_COOKIE['userID'])) {
    $userID = $_COOKIE['userID'];
    $user_history_query = mysqli_query($conn, "SELECT * FROM `user_history` WHERE user_id = '$userID' AND anime_id = '$url'");
    $user_history = mysqli_fetch_assoc($user_history_query);
    if (!$user_history) {
        // Add new entry for user history
        mysqli_query($conn, "INSERT INTO `user_history` (user_id, anime_id, anime_title, anime_ep, anime_image, anime_release, dubOrSub, anime_type)
                             VALUES('$userID', '$epurl', '$ANIME_NAME', '$EPISODE_NUM', '$ANIME_IMAGE', '$ANIME_RELEASED', '$dub', '$ANIME_TYPE')");
    } else {
        // Delete existing and re-insert updated history
        $user_history_id = $user_history['id'];
        mysqli_query($conn, "DELETE FROM `user_history` WHERE id = '$user_history_id'");
        mysqli_query($conn, "INSERT INTO `user_history` (user_id, anime_id, anime_title, anime_ep, anime_image, anime_release, dubOrSub, anime_type)
                             VALUES('$userID', '$epurl', '$ANIME_NAME', '$EPISODE_NUM', '$ANIME_IMAGE', '$ANIME_RELEASED', '$dub', '$ANIME_TYPE')");
    }
}
?>
<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#" xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <title>Watch <?=$anime['name'] ?> on <?=$websiteTitle ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="title" content="Watch <?=$anime['name'] ?> on <?=$websiteTitle ?>">
    <meta name="description" content="<?=substr($anime['description'], 0, 150) ?> ... at <?=$websiteUrl ?>">
    <meta name="keywords" content="<?=$websiteTitle ?>, <?=$anime['name'] ?>, watch anime online, free anime, anime stream, anime hd, english sub">
    <meta name="charset" content="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="robots" content="index, follow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta http-equiv="Content-Language" content="en">
    <meta property="og:title" content="Watch <?=$anime['name'] ?> on <?=$websiteTitle ?>">
    <meta property="og:description" content="<?=substr($anime['description'], 0, 150) ?> ... at <?=$websiteUrl ?>">
    <meta property="og:locale" content="en_US">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?=$websiteTitle ?>">
    <meta property="og:url" content="<?=$websiteUrl ?>/anime/<?=$url ?>">
    <meta itemprop="image" content="<?=$anime['poster'] ?>">
    <meta property="og:image" content="<?=$anime['poster'] ?>">
    <meta property="twitter:title" content="Watch <?=$anime['name'] ?> on <?=$websiteTitle ?>">
    <meta property="twitter:description" content="<?=substr($anime['description'], 0, 150) ?> ... at <?=$websiteUrl ?>">
    <meta property="twitter:url" content="<?=$websiteUrl ?>/anime/<?=$url ?>">
    <meta property="twitter:card" content="summary">
    <meta name="apple-mobile-web-app-status-bar" content="#202125">
    <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-63430163bc99824a"></script>
    <meta name="theme-color" content="#202125">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" type="text/css">
    <link rel="shortcut icon" href="<?=$websiteUrl ?>/favicon.ico?v=<?=$version ?>" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?=$websiteUrl ?>/favicon.ico?v=<?=$version ?>" />
    <link rel="stylesheet" href="<?=$websiteUrl ?>/files/css/style.css?v=<?=$version ?>">
    <link rel="stylesheet" href="<?=$websiteUrl ?>/files/css/min.css?v=<?=$version ?>">
</head>

<body data-page="movie_watch">
    <div id="sidebar_menu_bg"></div>
    <div id="wrapper" data-page="movie_watch">
        <?php include ('./_php/header.php'); ?>
        <div class="clearfix"></div>
        <div id="main-wrapper" date-page="movie_watch" data-id="">
            <div id="ani_detail">
                <div class="ani_detail-stage">
                    <div class="container">
                        <div class="anis-cover-wrap">
                            <div class="anis-cover" style="background-image: url('<?=$websiteUrl
?>/files/images/banner.webp')">
                            </div>
                        </div>
                        <div class="anis-watch-wrap">
                            <div class="prebreadcrumb">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem" class="breadcrumb-item">
                                            <a itemprop="item" href="/home"><span itemprop="name">Home</span></a>
                                            <meta itemprop="position" content="1">
                                        </li>
                                        <li itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem" class="breadcrumb-item">
                                            <a itemprop="item" href="/search?keyword=/"><span itemprop="name">Anime</span></a>
                                            <meta itemprop="position" content="2">
                                        </li>
                                        <li itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem" class="breadcrumb-item" aria-current="page">
                                            <a itemprop="item" href="/anime/<?=$animeID
?>"><span itemprop="name"><?=$anime['name'] ?></span></a>
                                            <meta itemprop="position" content="3">
                                        </li>
                                        <li itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem" class="breadcrumb-item" aria-current="page">
                                            <a itemprop="item" href="<?=$websiteUrl ?>/watch/<?=$epurl ?>"><span itemprop="name">Episode <?=$EPISODE_NUM ?></span></a>
                                            <meta itemprop="position" content="4">
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="anis-watch anis-watch-tv">
                                <div class="watch-player">
                                    <div class="player-frame">
                                        <div class="loading-relative loading-box" id="embed-loading">
                                            <div class="loading">
                                                <div class="span1"></div>
                                                <div class="span2"></div>
                                                <div class="span3"></div>
                                            </div>
                                        </div>
                                        <?php
// Fetch server data
$serverData = file_get_contents("$api/api/v2/hianime/episode/servers?animeEpisodeId=$EPISODEE_ID");
$serverData = json_decode($serverData, true);
// Initialize variables to store the URLs of the preferred servers
$preferredServer = "";
$serverLabel = "";
// Check for raw server hd-1 first
if (isset($serverData['data']['raw'])) {
    foreach ($serverData['data']['raw'] as $server) {
        if ($server['serverName'] == "hd-1") {
            $preferredServer = "https://server3.animegers.com/raw.php?id={$epurl}&server=hd-1&category=raw";
            $serverLabel = "RAW SVR: hd-1";
            break;
        }
    }
}
// If raw server hd-1 is not found, check for sub server hd-1
if (empty($preferredServer) && isset($serverData['data']['sub'])) {
    foreach ($serverData['data']['sub'] as $server) {
        if ($server['serverName'] == "hd-1") {
            $preferredServer = "https://server1.animegers.com/sub.php?id={$epurl}&server=hd-1&category=sub";
            $serverLabel = "SUB SVR: hd-1";
            break;
        }
    }
}
// If neither raw nor sub server hd-1 is found, check for dub server hd-1
if (empty($preferredServer) && isset($serverData['data']['dub'])) {
    foreach ($serverData['data']['dub'] as $server) {
        if ($server['serverName'] == "hd-1") {
            $preferredServer = "https://server2.animegers.com/dub.php?id={$epurl}&server=hd-1&category=dub";
            $serverLabel = "DUB SVR: hd-1";
            break;
        }
    }
}
// Default to existing sub server if no hd-1 server is found
if (empty($preferredServer)) {
    $preferredServer = "https://server1.animegers.com/sub.php?id={$epurl}&server=hd-1&category=sub";
    $serverLabel = "SUB SVR: default";
}
?>

<iframe name="iframe-to-load"
    src="<?=$preferredServer
?>" frameborder="0"
    scrolling="no"
    allow="accelerometer;autoplay;encrypted-media;gyroscope;picture-in-picture"
    allowfullscreen="true" webkitallowfullscreen="true"
    mozallowfullscreen="true"></iframe>
<div class="server-notice" style="text-align: center;"><strong>Currently watching on <?=$serverLabel ?></strong></div>
                                    </div>
                                    <div class="player-controls">
                                        <div class="pc-item pc-toggle pc-light">
                                            <div id="turn-off-light" class="toggle-basic">
                                                <span class="tb-name"><i class="fas fa-lightbulb mr-2"></i>Light</span>
                                                <span class="tb-result"></span>
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                                </div>
<?php
// Display servers with active class for the selected server
echo '<div class="player-servers">
    <div id="servers-content">
        <div class="ps_-status">
            <div class="content">
                <div class="server-notice"><strong>Currently watching <b>Episode ' . $EPISODE_NUM . '</b></strong> Switch to alternate servers in case of error or contact us in our discord.</div>
            </div>
        </div>';
if (isset($serverData['data']['sub']) && !empty($serverData['data']['sub'])) {
    echo '<div class="ps_-block ps_-block-sub servers-mixed">';
    echo '<div class="ps__-title"><i class="fas fa-server mr-2"></i>SUB SVR:</div>';
    echo '<div class="ps__-list">';
    foreach ($serverData['data']['sub'] as $server) {
        $serverName = $server['serverName'];
        $serverId = $server['serverId'];
        $activeClass = ($preferredServer == "https://server1.animegers.com/sub.php?id={$epurl}&server={$serverName}&category=sub") ? 'active' : '';
        echo "<div class='item'>
                <a id='server{$serverId}' href='https://server1.animegers.com/sub.php?id={$epurl}&server={$serverName}&category=sub' target='iframe-to-load' class='btn btn-server {$activeClass}'>{$serverName}</a>
              </div>";
    }
    echo '</div>';
    echo '<div class="clearfix"></div>';
    echo '<div id="source-guide"></div>';
    echo '</div>';
}
if (isset($serverData['data']['dub']) && !empty($serverData['data']['dub'])) {
    echo '<div class="ps_-block ps_-block-sub servers-mixed">';
    echo '<div class="ps__-title"><i class="fas fa-server mr-2"></i>DUB SVR:</div>';
    echo '<div class="ps__-list">';
    foreach ($serverData['data']['dub'] as $server) {
        $serverName = $server['serverName'];
        $serverId = $server['serverId'];
        $activeClass = ($preferredServer == "https://server2.animegers.com/dub.php?id={$epurl}&server={$serverName}&category=dub") ? 'active' : '';
        echo "<div class='item'>
                <a id='server{$serverId}' href='https://server2.animegers.com/dub.php?id={$epurl}&server={$serverName}&category=dub' target='iframe-to-load' class='btn btn-server {$activeClass}'>{$serverName}</a>
              </div>";
    }
    echo '</div>';
    echo '<div class="clearfix"></div>';
    echo '<div id="source-guide"></div>';
    echo '</div>';
}
if (isset($serverData['data']['raw']) && !empty($serverData['data']['raw'])) {
    echo '<div class="ps_-block ps_-block-raw servers-mixed">';
    echo '<div class="ps__-title"><i class="fas fa-server mr-2"></i>RAW SVR:</div>';
    echo '<div class="ps__-list">';
    foreach ($serverData['data']['raw'] as $server) {
        $serverName = $server['serverName'];
        $serverId = $server['serverId'];
        $activeClass = ($preferredServer == "https://server3.animegers.com/raw.php?id={$epurl}&server={$serverName}&category=raw") ? 'active' : '';
        echo "<div class='item'>
                <a id='server{$serverId}' href='https://server3.animegers.com/raw.php?id={$epurl}&server={$serverName}&category=raw' target='iframe-to-load' class='btn btn-server {$activeClass}'>{$serverName}</a>
              </div>";
    }
    echo '</div>';
    echo '<div class="clearfix"></div>';
    echo '<div id="source-guide"></div>';
    echo '</div>';
}
echo '</div>
</div>';
?>

                                <div id="episodes-content">
                                    <div class="seasons-block seasons-block-max">
                                        <div id="detail-ss-list" class="detail-seasons">
                                            <div class="detail-infor-content">
                                                <div style="min-height:43px;" class="ss-choice">
                                                    <div class="ssc-list">
                                                        <div id="ssc-list" class="ssc-button">
                                                            <div class="ssc-label">List of episodes:</div>
                                                        </div>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div id="episodes-page-1" class="ss-list ss-list-min" data-page="1" style="display:block;">
                                                    <?php foreach ($episodeList as $episode) { ?>
                                                        <a title="Episode <?=$episode['number'] ?>" class="ssl-item ep-item <?php if ($EPISODE_NUM == $episode['number']) {
        echo 'active';
    } ?>" href="/watch/<?=$episode['episodeId'] ?>">
                                                            <div class="ssli-order" title="<?=$episode['title'] ?>"><?=$episode['number'] ?></div>
                                                            <div class="ssli-detail">
                                                                <div class="ep-name dynamic-name" data-jname="" title=""><?=$episode['title'] ?></div>
                                                            </div>
                                                            <div class="ssli-btn">
                                                                <div class="btn btn-circle"><i class="fa-brands fa-google-play"></i>
                                                                </div>
                                                            </div>
                                                            <div class="clearfix"></div>
                                                        </a>
                                                    <?php
} ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="anis-watch-detail">
                                <div class="anis-content">
                                    <div class="anisc-poster">
                                        <div class="film-poster">
                                            <img src="<?=$anime['poster'] ?>" data-src="<?=$anime['poster'] ?>" class="film-poster-img ls-is-cached lazyloaded" alt="<?=$anime['name'] ?>">
                                        </div>
                                    </div>
                                    <div class="anisc-detail">
    <h2 class="film-name">
        <a href="/anime/<?=$animeID ?>" class="text-white dynamic-name" title="<?=$anime['name'] ?>" data-jname="<?=$anime['name'] ?>" style="opacity: 1;"><?=$anime['name'] ?></a>
    </h2>
    <div class="film-stats">
        <div class="tac tick-item tick-quality">HD</div>
        <div class="tac tick-item tick-dub">SUB, DUB?</div>
        <div class="tac tick-item tick-dub">
            <?php if ($counter) {
    echo "VIEWS: " . $counter;
} ?>
        </div>
        <span class="dot"></span>
        <span class="item">
            <?=$getAnime['data']['anime']['moreInfo']['status'] ?>
        </span>
        <span class="dot"></span>
        <span class="item">
            <?=$getAnime['data']['anime']['moreInfo']['aired'] ?>
        </span>
        <span class="dot"></span>
        <span class="item">
            <?=$getAnime['data']['anime']['moreInfo']['synonyms'] ?>
        </span>
        <span class="dot"></span>
        <span class="item">
            <?=$getAnime['data']['anime']['moreInfo']['premiered'] ?> Anime
        </span>
        <div class="clearfix"></div>
    </div>
    <div class="film-description m-hide">
        <div class="text">
            <?=$anime['description'] ?>
        </div>
    </div>
    <div class="film-text m-hide mb-3">
        <?=$websiteTitle
?> is a site to watch online anime like
        <strong>
            <?=$anime['name'] ?>
        </strong> online, or you can even watch
        <strong>
            <?=$anime['name'] ?>
        </strong> in HD quality
    </div>
    <div class="block"><a href="/anime/<?=$animeID
?>" class="btn btn-xs btn-light"><i class="fas fa-book-open mr-2"></i> View detail</a></div>

    <?php
$likeClass = "far";
if (isset($_COOKIE['like_' . $id])) {
    $likeClass = "fas";
}
$dislikeClass = "far";
if (isset($_COOKIE['dislike_' . $id])) {
    $dislikeClass = "fas";
}
?>
    <div class="dt-rate">
        <div id="vote-info">
            <div class="block-rating">
                <div class="rating-result">
                    <div class="rr-mark float-left">
                        <strong><i class="fas fa-star text-warning mr-2"></i><span id="ratingAnime"><?=round((10 * $like_count + 5 * $dislike_count) / ($like_count + $dislike_count), 2) ?></span></strong>
                        <small id="votedCount">(<?=$totalVotes ?> Voted)</small>
                    </div>
                    <div class="rr-title float-right">Vote now</div>
                    <div class="clearfix"></div>
                </div>
                <div class="description">What do you think about this episode?</div>
                <div class="button-rate">
                    <button type="button" onclick="setLikeDislike('dislike','<?=$id ?>')" class="btn btn-emo rate-bad btn-vote" style="width:50%" data-mark="dislike"><i id="dislike" class="<?=$dislikeClass ?> fa-thumbs-down"></i><span id="dislikeMsg" class="ml-2">Dislike</span></button>
                    <button onclick="setLikeDislike('like','<?=$id ?>')" type="button" class="btn btn-emo rate-good btn-vote" style="width:50%"><i id="like" class="<?=$likeClass ?> fa-thumbs-up"> </i><span id="likeMsg" class="ml-2">Like</span></button>
                    <div class="clearfix"></div>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
</div>
<div class="clearfix"></div>
</div>
</div>
</div>
</div>
<div class="share-buttons share-buttons-min mt-3">
<div class="container">
<div class="share-buttons-block">
<div class="share-icon"></div>
<div class="sbb-title">
    <span>Share Anime</span>
    <p class="mb-0">to your friends ðŸ˜˜</p>
</div>
<div class="sharethis-inline-share-buttons"></div>
<div class="clearfix"></div>
</div>
</div>
</div>
<div class="container">
<div id="main-content">
<section class="block_area block_area-comment">
<div class="block_area-header block_area-header-tabs">
    <div class="float-left bah-heading mr-4">
        <h2 class="cat-heading">Comments</h2>
    </div>
    <div class="float-left bah-setting"></div>
    <div class="clearfix"></div>
</div>
<div class="tab-content">
    <?php include ('./_php/disqus.php'); ?>
</div>
</section>
<section class="block_area block_area_category">
                        <div class="block_area-header">
                            <div class="float-left bah-heading mr-4">
                                <h2 class="cat-heading"><i>Recommended Anime >>></i></h2>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <div class="tab-content">
                            <div class="block_area-content block_area-list film_list film_list-grid film_list-wfeature">
                                <div class="film_list-wrap">
<?php
$json = file_get_contents("$api/api/v2/hianime/anime/$url");
$json = json_decode($json, true);
$recommendedAnimes = $json['data']['recommendedAnimes'];
foreach ($recommendedAnimes as $anime) { ?>
    <div class="flw-item ">
        <div class="film-poster">
            <div class="tick ltr">
                <div class="tick-item-sub tick-eps amp-algn">Ep Sub: <?=$anime['episodes']['sub']??'N/A' ?></div>
            </div>
            <div class="tick rtl">
                <div class="tick-item tick-eps amp-algn">Ep Dub: <?=$anime['episodes']['dub']??'N/A' ?></div>
            </div>
            <img class="film-poster-img lazyload"
                data-src="<?=$anime['poster'] ?>"
                src="<?=$websiteUrl ?>/files/images/no_poster.jpg"
                alt="<?=$anime['jname'] ?>">
            <a class="film-poster-ahref"
                href="/anime/<?=$anime['id'] ?>"
                title="<?=$anime['name'] ?>"
                data-jname="<?=$anime['jname'] ?>"><i class="fa-brands fa-google-play"></i></a>
        </div>
        <div class="film-detail">
            <h3 class="film-name">
                <a
                    href="/anime/<?=$anime['id'] ?>"
                    title="<?=$anime['name'] ?>"
                    data-jname="<?=$anime['jname'] ?>"><?=$anime['jname'] ?></a>
            </h3>
            <div class="fd-infor">
                <span class="fdi-item"><?=$anime['type'] ?></span>
                <span class="dot"></span>
                <span class="fdi-item">Recommended</span>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
<?php
} ?>

                                </div>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                    </section>
                    <section class="block_area block_area_category">
                        <div class="block_area-header">
                            <div class="float-left bah-heading mr-4">
                                <h2 class="cat-heading"><i>More To Watch >>></i></h2>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <div class="tab-content">
                            <div class="block_area-content block_area-list film_list film_list-grid film_list-wfeature">
                                <div class="film_list-wrap">
<?php
$json = file_get_contents("$api/api/v2/hianime/anime/$url");
$json = json_decode($json, true);
$relatedAnimes = $json['data']['relatedAnimes'];
foreach ($relatedAnimes as $anime) { ?>
    <div class="flw-item ">
        <div class="film-poster">
            <div class="tick ltr">
                <div class="tick-item-sub tick-eps amp-algn">Ep Sub: <?=$anime['episodes']['sub']??'N/A' ?></div>
            </div>
            <div class="tick rtl">
                <div class="tick-item tick-eps amp-algn">Ep Dub: <?=$anime['episodes']['dub']??'N/A' ?></div>
            </div>
            <img class="film-poster-img lazyload"
                data-src="<?=$anime['poster'] ?>"
                src="<?=$websiteUrl ?>/files/images/no_poster.jpg"
                alt="<?=$anime['jname'] ?>">
            <a class="film-poster-ahref"
                href="/anime/<?=$anime['id'] ?>"
                title="<?=$anime['name'] ?>"
                data-jname="<?=$anime['jname'] ?>"><i class="fa-brands fa-google-play"></i></a>
        </div>
        <div class="film-detail">
            <h3 class="film-name">
                <a
                    href="/anime/<?=$anime['id'] ?>"
                    title="<?=$anime['name'] ?>"
                    data-jname="<?=$anime['jname'] ?>"><?=$anime['jname'] ?></a>
            </h3>
            <div class="fd-infor">
                <span class="fdi-item"><?=$anime['type'] ?></span>
                <span class="dot"></span>
                <span class="fdi-item">Must Watch</span>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
<?php
} ?>

                                </div>
                                <div class="clearfix"></div>
                            </div>
                        </div>
</section>
<div class="clearfix"></div>
</div>
<?php include ('./_php/sidenav.php'); ?>
<div class="clearfix"></div>
</div>
</div>
<?php include ('./_php/footer.php'); ?>
<div id="mask-overlay"></div>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js?v=<?=$version
?>"></script>
<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.bundle.min.js?v=<?=$version
?>"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/js-cookie@rc/dist/js.cookie.min.js"></script>
<script type="text/javascript" src="<?=$websiteUrl
?>/files/js/app.js?v=<?=$version
?>"></script>
<script type="text/javascript" src="<?=$websiteUrl
?>/files/js/comman.js?v=<?=$version
?>"></script>
<script type="text/javascript" src="<?=$websiteUrl
?>/files/js/movie.js?v=<?=$version
?>"></script>
<link rel="stylesheet" href="<?=$websiteUrl
?>/files/css/jquery-ui.css?v=<?=$version
?>">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js?v=<?=$version
?>"></script>
<script type="text/javascript">
    $(".btn-server").click(function () {
        $(".btn-server").removeClass("active");
        $(this).closest(".btn-server").addClass("active");
    });
</script>
<script type="text/javascript">
    if ('<?=$likeClass
?>' === 'fas') {
        document.getElementById('likeMsg').innerHTML = "Liked"
    }
    if ('<?=$dislikeClass
?>' === 'fas') {
        document.getElementById('dislikeMsg').innerHTML = "Disliked"
    }

    function setLikeDislike(type, id) {
        jQuery.ajax({
            url: '<?=$websiteUrl
?>/setLikeDislike.php',
            type: 'post',
            data: 'type=' + type + '&id=' + id,
            success: function (result) {
                result = jQuery.parseJSON(result);
                if (result.opertion == 'like') {
                    jQuery('#like').removeClass('far');
                    jQuery('#like').addClass('fas');
                    jQuery('#dislike').addClass('far');
                    jQuery('#dislike').removeClass('fas');
                    jQuery('#likeMsg').html("Liked")
                    jQuery('#dislikeMsg').html("Dislike")
                }
                if (result.opertion == 'unlike') {
                    jQuery('#like').addClass('far');
                    jQuery('#like').removeClass('fas');
                    jQuery('#likeMsg').html("Like")
                }

                if (result.opertion == 'dislike') {
                    jQuery('#dislike').removeClass('far');
                    jQuery('#dislike').addClass('fas');
                    jQuery('#like').addClass('far');
                    jQuery('#like').removeClass('fas');
                    jQuery('#dislikeMsg').html("Disliked")
                    jQuery('#likeMsg').html("Like")
                }
                if (result.opertion == 'undislike') {
                    jQuery('#dislike').addClass('far');
                    jQuery('#dislike').removeClass('fas');
                    jQuery('#dislikeMsg').html("Dislike")
                }

                jQuery('#votedCount').html(`(${parseInt(result.like_count) + parseInt(result.dislike_count)} Voted)`);
                jQuery('#ratingAnime').html(((parseInt(result.like_count) * 10 + parseInt(result.dislike_count) * 5) / (parseInt(result.like_count) + parseInt(result.dislike_count))).toFixed(2));
            }
        });
    }
</script>
</div>
</body>
</html>
