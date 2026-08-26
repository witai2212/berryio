<?
/*------------------------------------------------------------------------------
  BerryIO responsive Bootstrap layout
  Date: 2026-08-26 | Revision: 1
------------------------------------------------------------------------------*/
?>
<!doctype html>
<html lang="en-GB" data-bs-theme="dark">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="author" content="<?=h(NAME)?>" />
    <meta name="copyright" content="<?=h(NAME)?>" />
    <meta name="theme-color" content="#111827" />
    <title><?=h(NAME)?></title>
    <link rel="stylesheet" href="/vendor/bootstrap/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/css/main.css?11" type="text/css" />

    <? foreach($GLOBALS['JAVASCRIPT_RUN'] as $type => $javascript):?>
      <?= view('javascript/'.$javascript, isset($GLOBALS['JAVASCRIPT_DATA'][$type]) ? $GLOBALS['JAVASCRIPT_DATA'][$type] : '')?>
    <? endforeach?>
    <? foreach($GLOBALS['JAVASCRIPT'] as $type => $javascript):?>
      <?= view('javascript/'.$javascript, isset($GLOBALS['JAVASCRIPT_DATA'][$type]) ? $GLOBALS['JAVASCRIPT_DATA'][$type] : '')?>
    <? endforeach?>
  </head>

  <body onload="<?= isset($_GET['s']) && is_numeric($_GET['s']) ? 'window.scrollTo(0, '.($_GET['s'] + 0).');' : ''?><? foreach ($GLOBALS['JAVASCRIPT_RUN'] as $javascript):?><?= $javascript?>();<? endforeach?>">
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top berryio-navbar" aria-label="Main navigation">
      <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/welcome">
          <span class="brand-mark" aria-hidden="true">B</span>
          <span><?=h(NAME)?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#berryioMenu" aria-controls="berryioMenu" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="berryioMenu">
          <div id="menu" class="navbar-nav ms-auto py-2 py-lg-0">
            <? foreach($GLOBALS['MENU'] as $link => $name):?>
              <? $active = $link == $selected; $power = $link == 'shutdown' || $link == 'reboot';?>
              <a class="nav-link<?= $active ? ' active' : ''?><?= $power ? ' power-link' : ''?>" href="/<?=$link?>"<?= $active ? ' aria-current="page"' : ''?>><?=h($name)?></a>
            <? endforeach?>
          </div>
        </div>
      </div>
    </nav>

    <main id="main" class="container-fluid px-3 px-lg-4 py-4">
      <? if($GLOBALS['TITLE'] !== FALSE):?>
        <header class="page-header mx-auto">
          <div>
            <span class="eyebrow">Raspberry Pi control center</span>
            <h1 id="title"><?=h($GLOBALS['TITLE'])?></h1>
          </div>
          <span class="status-badge"><span class="status-dot"></span>Online</span>
        </header>
      <? endif?>
      <section class="dashboard-content" aria-live="polite">
        <?=$content?>
      </section>
    </main>

    <footer class="app-footer">
      <span><?=h(NAME)?> · Raspberry Pi control center</span>
    </footer>
    <script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
