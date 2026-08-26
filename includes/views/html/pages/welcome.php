<?
/*------------------------------------------------------------------------------
  BerryIO dashboard welcome view
  Date: 2026-08-26 | Revision: 1
------------------------------------------------------------------------------*/
?>
<div class="welcome-hero">
  <div class="welcome-copy">
    <span class="eyebrow">Device ready</span>
    <h1>Welcome to <?=h(NAME)?></h1>
    <p>Monitor your Raspberry Pi and control connected hardware from one responsive dashboard.</p>
  </div>
  <img class="welcome-logo" src="/images/layout/logo.png" alt="<?=h(NAME)?>" />
</div>

<div class="quick-actions">
  <a class="quick-action" href="/gpio_status"><strong>GPIO</strong><span>Control pins and outputs</span></a>
  <a class="quick-action" href="/system_status"><strong>System</strong><span>Review device health</span></a>
  <a class="quick-action" href="/network_status"><strong>Network</strong><span>Inspect connectivity</span></a>
  <a class="quick-action" href="/camera_status"><strong>Camera</strong><span>Capture and manage images</span></a>
</div>

<p class="javascript-note">JavaScript must be enabled for interactive controls.</p>
