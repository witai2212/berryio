<?
/*------------------------------------------------------------------------------
  BerryIO GPIO Configuration Page
  Date: 2026-08-26 | Revision: 1
------------------------------------------------------------------------------*/
?>

<? if($saved):?>
  <div class="alert alert-success" role="status">GPIO configuration saved.</div>
<? endif?>

<? if(count($errors)):?>
  <div class="alert alert-danger" role="alert">
    <strong>The configuration was not saved.</strong>
    <ul class="mb-0 mt-2">
      <? foreach(array_unique($errors) as $error):?>
        <li><?=h($error)?></li>
      <? endforeach?>
    </ul>
  </div>
<? endif?>

<form method="post" action="/gpio_config" class="gpio-config-form">
  <input type="hidden" name="token" value="<?=h($token)?>" />

  <section class="card berryio-card mb-4">
    <div class="card-body">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <div>
          <h2 class="h4 mb-1">GPIO pins</h2>
          <p class="text-body-secondary mb-0">Use BCM GPIO numbers from 0 to 53. Every number may occur only once.</p>
        </div>
        <button type="button" id="add-gpio-pin" class="btn btn-outline-light">Add pin</button>
      </div>

      <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">GPIO number</th>
              <th scope="col">Name</th>
              <th scope="col" class="text-end">Action</th>
            </tr>
          </thead>
          <tbody id="gpio-pin-rows">
            <? foreach($pins as $pin => $name):?>
              <tr>
                <td><input class="form-control" type="number" name="pin_number[]" min="0" max="53" required value="<?=h($pin)?>" aria-label="GPIO number" /></td>
                <td><input class="form-control" type="text" name="pin_name[]" maxlength="80" value="<?=h($name)?>" aria-label="GPIO name" /></td>
                <td class="text-end"><button type="button" class="btn btn-outline-danger remove-gpio-pin">Remove</button></td>
              </tr>
            <? endforeach?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="card berryio-card mb-4">
    <div class="card-body">
      <h2 class="h4 mb-3">Display</h2>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="pins-per-row">Pins per row</label>
          <input class="form-control" id="pins-per-row" type="number" name="pins_per_row" min="1" max="12" required value="<?=h($pins_per_row)?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label" for="update-interval">Update interval (milliseconds)</label>
          <input class="form-control" id="update-interval" type="number" name="update_interval" min="100" max="60000" step="100" required value="<?=h($update_interval)?>" />
        </div>
      </div>
    </div>
  </section>

  <div class="d-flex flex-wrap gap-2">
    <button type="submit" class="btn btn-primary">Save configuration</button>
    <a class="btn btn-outline-light" href="/gpio_status">Back to GPIO control</a>
  </div>
</form>

<template id="gpio-pin-row-template">
  <tr>
    <td><input class="form-control" type="number" name="pin_number[]" min="0" max="53" required aria-label="GPIO number" /></td>
    <td><input class="form-control" type="text" name="pin_name[]" maxlength="80" aria-label="GPIO name" /></td>
    <td class="text-end"><button type="button" class="btn btn-outline-danger remove-gpio-pin">Remove</button></td>
  </tr>
</template>

<script>
document.getElementById('add-gpio-pin').addEventListener('click', function() {
  var template = document.getElementById('gpio-pin-row-template');
  var row = template.content.cloneNode(true);
  document.getElementById('gpio-pin-rows').appendChild(row);
});

document.getElementById('gpio-pin-rows').addEventListener('click', function(event) {
  if(event.target.classList.contains('remove-gpio-pin'))
    event.target.closest('tr').remove();
});
</script>
