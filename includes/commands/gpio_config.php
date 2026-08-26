<?
/*------------------------------------------------------------------------------
  BerryIO GPIO Configuration Command
  Date: 2026-08-26 | Revision: 1
------------------------------------------------------------------------------*/

$title = 'GPIO Configuration';

require_once(FUNCTIONS.'gpio.php');

if(session_id() == '') session_start();
if(!isset($_SESSION['gpio_config_token']))
  $_SESSION['gpio_config_token'] = bin2hex(random_bytes(24));

$page = array(
  'pins' => $GLOBALS['GPIO_PINS'],
  'pins_per_row' => GPIO_PINS_PER_ROW,
  'update_interval' => GPIO_UPDATE_INTERVAL,
  'token' => $_SESSION['gpio_config_token'],
  'errors' => array(),
  'saved' => isset($_GET['saved']),
);

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
  $page['pins'] = array();
  $pin_numbers = isset($_POST['pin_number']) && is_array($_POST['pin_number']) ? $_POST['pin_number'] : array();
  $pin_names = isset($_POST['pin_name']) && is_array($_POST['pin_name']) ? $_POST['pin_name'] : array();
  $page['pins_per_row'] = isset($_POST['pins_per_row']) ? trim($_POST['pins_per_row']) : '';
  $page['update_interval'] = isset($_POST['update_interval']) ? trim($_POST['update_interval']) : '';

  if(!isset($_POST['token']) || !is_string($_POST['token']) || !hash_equals($_SESSION['gpio_config_token'], $_POST['token']))
    $page['errors'][] = 'The form has expired. Please reload the page and try again.';

  foreach($pin_numbers as $index => $pin)
  {
    if(!is_scalar($pin))
    {
      $page['errors'][] = 'GPIO numbers must be whole numbers between 0 and 53.';
      continue;
    }
    $pin = trim($pin);
    $name = isset($pin_names[$index]) && is_scalar($pin_names[$index]) ? trim($pin_names[$index]) : '';
    if($pin === '' && $name === '') continue;

    if(!ctype_digit($pin) || (int)$pin < 0 || (int)$pin > 53)
    {
      $page['errors'][] = 'GPIO numbers must be whole numbers between 0 and 53.';
      continue;
    }

    $pin = (int)$pin;
    if(array_key_exists($pin, $page['pins']))
    {
      $page['errors'][] = 'GPIO-'.$pin.' is listed more than once.';
      continue;
    }

    if(strlen($name) > 80)
    {
      $page['errors'][] = 'The name for GPIO-'.$pin.' must not exceed 80 characters.';
      continue;
    }

    $page['pins'][$pin] = $name;
  }

  if(count($page['pins']) == 0)
    $page['errors'][] = 'At least one GPIO pin must be configured.';

  if(!ctype_digit($page['pins_per_row']) || (int)$page['pins_per_row'] < 1 || (int)$page['pins_per_row'] > 12)
    $page['errors'][] = 'Pins per row must be between 1 and 12.';

  if(!ctype_digit($page['update_interval']) || (int)$page['update_interval'] < 100 || (int)$page['update_interval'] > 60000)
    $page['errors'][] = 'The update interval must be between 100 and 60000 milliseconds.';

  if(count($page['errors']) == 0)
  {
    ksort($page['pins'], SORT_NUMERIC);
    if(gpio_save_config($page['pins'], (int)$page['pins_per_row'], (int)$page['update_interval']))
    {
      $_SESSION['gpio_config_token'] = bin2hex(random_bytes(24));
      header('Location: /gpio_config?saved=1');
      exit();
    }

    $page['errors'][] = 'The configuration could not be saved. Check that '.SETTINGS.'gpio.php is writable by the web server.';
  }
}

$content .= view('pages/gpio_config', $page);
