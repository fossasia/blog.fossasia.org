<?php
// $Id: drupal_web_test_case.php,v 1.2.2.3.2.46 2009/11/06 21:23:32 boombatower Exp $
// Core: Id: drupal_web_test_case.php,v 1.146 2009/08/31 18:30:26 webchick Exp $

/**
 * @file
 * Provide required modifications to Drupal 7 core DrupalWebTestCase in order
 * for it to function properly in Drupal 6.
 *
 * Copyright 2008-2009 by Jimmy Berry ("boombatower", http://drupal.org/user/214218)
 */

module_load_include('function.inc', 'simpletest');

/**
 * Base class for Drupal tests.
 *
 * Do not extend this class, use one of the subclasses in this file.
 */
abstract class DrupalTestCase {
  /**
   * The test run ID.
   *
   * @var string
   */
  protected $testId;

  /**
   * The original database prefix, before it was changed for testing purposes.
   *
   * @var string
   */
  protected $originalPrefix = NULL;

  /**
   * The original file directory, before it was changed for testing purposes.
   *
   * @var string
   */
  protected $originalFileDirectory = NULL;

  /**
   * Time limit for the test.
   */
  protected $timeLimit = 180;

  /**
   * Current results of this test case.
   *
   * @var Array
   */
  public $results = array(
    '#pass' => 0,
    '#fail' => 0,
    '#exception' => 0,
    '#debug' => 0,
  );

  /**
   * Assertions thrown in that test case.
   *
   * @var Array
   */
  protected $assertions = array();

  /**
   * This class is skipped when looking for the source of an assertion.
   *
   * When displaying which function an assert comes from, it's not too useful
   * to see "drupalWebTestCase->drupalLogin()', we would like to see the test
   * that called it. So we need to skip the classes defining these helper
   * methods.
   */
  protected $skipClasses = array(__CLASS__ => TRUE);

  /**
   * Constructor for DrupalWebTestCase.
   *
   * @param $test_id
   *   Tests with the same id are reported together.
   */
  public function __construct($test_id = NULL) {
    $this->testId = $test_id;
  }

  /**
   * Internal helper: stores the assert.
   *
   * @param $status
   *   Can be 'pass', 'fail', 'exception'.
   *   TRUE is a synonym for 'pass', FALSE for 'fail'.
   * @param $message
   *   The message string.
   * @param $group
   *   Which group this assert belongs to.
   * @param $caller
   *   By default, the assert comes from a function whose name starts with
   *   'test'. Instead, you can specify where this assert originates from
   *   by passing in an associative array as $caller. Key 'file' is
   *   the name of the source file, 'line' is the line number and 'function'
   *   is the caller function itself.
   */
  protected function assert($status, $message = '', $group = 'Other', array $caller = NULL) {
    global $db_prefix;

    // Convert boolean status to string status.
    if (is_bool($status)) {
      $status = $status ? 'pass' : 'fail';
    }

    // Increment summary result counter.
    $this->results['#' . $status]++;

    // Get the function information about the call to the assertion method.
    if (!$caller) {
      $caller = $this->getAssertionCall();
    }

    // Switch to non-testing database to store results in.
    $current_db_prefix = $db_prefix;
    $db_prefix = $this->originalPrefix;

    // Creation assertion array that can be displayed while tests are running.
    $this->assertions[] = $assertion = array(
      'test_id' => $this->testId,
      'test_class' => get_class($this),
      'status' => $status,
      'message' => $message,
      'message_group' => $group,
      'function' => $caller['function'],
      'line' => $caller['line'],
      'file' => $caller['file'],
    );

    // Store assertion for display after the test has completed.
//    db_insert('simpletest')
//      ->fields($assertion)
//      ->execute();
    db_query("INSERT INTO {simpletest}
              (test_id, test_class, status, message, message_group, function, line, file)
              VALUES (%d, '%s', '%s', '%s', '%s', '%s', %d, '%s')", array_values($assertion));

    // Return to testing prefix.
    $db_prefix = $current_db_prefix;
    // We do not use a ternary operator here to allow a breakpoint on
    // test failure.
    if ($status == 'pass') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Store an assertion from outside the testing context.
   *
   * This is useful for inserting assertions that can only be recorded after
   * the test case has been destroyed, such as PHP fatal errors. The caller
   * information is not automatically gathered since the caller is most likely
   * inserting the assertion on behalf of other code. In all other respects
   * the method behaves just like DrupalTestCase::assert() in terms of storing
   * the assertion.
   *
   * @see DrupalTestCase::assert()
   */
  public static function insertAssert($test_id, $test_class, $status, $message = '', $group = 'Other', array $caller = array()) {
    // Convert boolean status to string status.
    if (is_bool($status)) {
      $status = $status ? 'pass' : 'fail';
    }

    $caller += array(
      'function' => t('Unknown'),
      'line' => 0,
      'file' => t('Unknown'),
    );

    $assertion = array(
      'test_id' => $test_id,
      'test_class' => $test_class,
      'status' => $status,
      'message' => $message,
      'message_group' => $group,
      'function' => $caller['function'],
      'line' => $caller['line'],
      'file' => $caller['file'],
    );

//    db_insert('simpletest')
//      ->fields($assertion)
//      ->execute();
    db_query("INSERT INTO {simpletest}
              (test_id, test_class, status, message, message_group, function, line, file)
              VALUES (%d, '%s', '%s', '%s', '%s', '%s', %d, '%s')", array_values($assertion));
  }

  /**
   * Cycles through backtrace until the first non-assertion method is found.
   *
   * @return
   *   Array representing the true caller.
   */
  protected function getAssertionCall() {
    $backtrace = debug_backtrace();

    // The first element is the call. The second element is the caller.
    // We skip calls that occurred in one of the methods of our base classes
    // or in an assertion function.
   while (($caller = $backtrace[1]) &&
         ((isset($caller['class']) && isset($this->skipClasses[$caller['class']])) ||
           substr($caller['function'], 0, 6) == 'assert')) {
      // We remove that call.
      array_shift($backtrace);
    }

    return _drupal_get_last_caller($backtrace);
  }

  /**
   * Check to see if a value is not false (not an empty string, 0, NULL, or FALSE).
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertTrue($value, $message = '', $group = 'Other') {
    return $this->assert((bool) $value, $message ? $message : t('Value is TRUE'), $group);
  }

  /**
   * Check to see if a value is false (an empty string, 0, NULL, or FALSE).
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertFalse($value, $message = '', $group = 'Other') {
    return $this->assert(!$value, $message ? $message : t('Value is FALSE'), $group);
  }

  /**
   * Check to see if a value is NULL.
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNull($value, $message = '', $group = 'Other') {
    return $this->assert(!isset($value), $message ? $message : t('Value is NULL'), $group);
  }

  /**
   * Check to see if a value is not NULL.
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNotNull($value, $message = '', $group = 'Other') {
    return $this->assert(isset($value), $message ? $message : t('Value is not NULL'), $group);
  }

  /**
   * Check to see if two values are equal.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertEqual($first, $second, $message = '', $group = 'Other') {
    return $this->assert($first == $second, $message ? $message : t('First value is equal to second value'), $group);
  }

  /**
   * Check to see if two values are not equal.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNotEqual($first, $second, $message = '', $group = 'Other') {
    return $this->assert($first != $second, $message ? $message : t('First value is not equal to second value'), $group);
  }

  /**
   * Check to see if two values are identical.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertIdentical($first, $second, $message = '', $group = 'Other') {
    return $this->assert($first === $second, $message ? $message : t('First value is identical to second value'), $group);
  }

  /**
   * Check to see if two values are not identical.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNotIdentical($first, $second, $message = '', $group = 'Other') {
    return $this->assert($first !== $second, $message ? $message : t('First value is not identical to second value'), $group);
  }

  /**
   * Fire an assertion that is always positive.
   *
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   TRUE.
   */
  protected function pass($message = NULL, $group = 'Other') {
    return $this->assert(TRUE, $message, $group);
  }

  /**
   * Fire an assertion that is always negative.
   *
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   FALSE.
   */
  protected function fail($message = NULL, $group = 'Other') {
    return $this->assert(FALSE, $message, $group);
  }

  /**
   * Fire an error assertion.
   *
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @param $caller
   *   The caller of the error.
   * @return
   *   FALSE.
   */
  protected function error($message = '', $group = 'Other', array $caller = NULL) {
    if ($group == 'User notice') {
      // Since 'User notice' is set by trigger_error() which is used for debug
      // set the message to a status of 'debug'.
      return $this->assert('debug', $message, 'Debug', $caller);
    }

    return $this->assert('exception', $message, $group, $caller);
  }

  /**
   * Run all tests in this class.
   */
  public function run() {
    // Initialize verbose debugging.
    simpletest_verbose(NULL, file_directory_path(), get_class($this));

    // HTTP auth settings (<username>:<password>) for the simpletest browser
    // when sending requests to the test site.
    $username = variable_get('simpletest_username', NULL);
    $password = variable_get('simpletest_password', NULL);
    if ($username && $password) {
      $this->httpauth_credentials = $username . ':' . $password;
    }

    set_error_handler(array($this, 'errorHandler'));
    $methods = array();
    // Iterate through all the methods in this class.
    foreach (get_class_methods(get_class($this)) as $method) {
      // If the current method starts with "test", run it - it's a test.
      if (strtolower(substr($method, 0, 4)) == 'test') {
        $this->setUp();
        try {
          $this->$method();
          // Finish up.
        }
        catch (Exception $e) {
          $this->exceptionHandler($e);
        }
        $this->tearDown();
      }
    }
    // Clear out the error messages and restore error handler.
    drupal_get_messages();
    restore_error_handler();
  }

  /**
   * Handle errors.
   *
   * Because this is registered in set_error_handler(), it has to be public.
   * @see set_error_handler
   *
   */
  public function errorHandler($severity, $message, $file = NULL, $line = NULL) {
    if ($severity & error_reporting()) {
      $error_map = array(
        E_STRICT => 'Run-time notice',
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core error',
        E_CORE_WARNING => 'Core warning',
        E_USER_ERROR => 'User error',
        E_USER_WARNING => 'User warning',
        E_USER_NOTICE => 'User notice',
        E_RECOVERABLE_ERROR => 'Recoverable error',
      );

      $backtrace = debug_backtrace();
      $this->error($message, $error_map[$severity], _drupal_get_last_caller($backtrace));
    }
    return TRUE;
  }

  /**
   * Handle exceptions.
   *
   * @see set_exception_handler
   */
  protected function exceptionHandler($exception) {
    $backtrace = $exception->getTrace();
    // Push on top of the backtrace the call that generated the exception.
    array_unshift($backtrace, array(
      'line' => $exception->getLine(),
      'file' => $exception->getFile(),
    ));
    $this->error($exception->getMessage(), 'Uncaught exception', _drupal_get_last_caller($backtrace));
  }

  /**
   * Generates a random string of ASCII characters of codes 32 to 126.
   *
   * The generated string includes alpha-numeric characters and common misc
   * characters. Use this method when testing general input where the content
   * is not restricted.
   *
   * @param $length
   *   Length of random string to generate which will be appended to $db_prefix.
   * @return
   *   Randomly generated string.
   */
  public static function randomString($length = 8) {
    global $db_prefix;

    $str = '';
    for ($i = 0; $i < $length; $i++) {
      $str .= chr(mt_rand(32, 126));
    }
    return str_replace('simpletest', 's', $db_prefix) . $str;
  }

  /**
   * Generates a random string containing letters and numbers.
   *
   * The letters may be upper or lower case. This method is better for
   * restricted inputs that do not accept certain characters. For example,
   * when testing input fields that require machine readable values (ie without
   * spaces and non-standard characters) this method is best.
   *
   * @param $length
   *   Length of random string to generate which will be appended to $db_prefix.
   * @return
   *   Randomly generated string.
   */
  public static function randomName($length = 8) {
    global $db_prefix;

    $values = array_merge(range(65, 90), range(97, 122), range(48, 57));
    $max = count($values) - 1;
    $str = '';
    for ($i = 0; $i < $length; $i++) {
      $str .= chr($values[mt_rand(0, $max)]);
    }
    return str_replace('simpletest', 's', $db_prefix) . $str;
  }

}

/**
 * Test case for Drupal unit tests.
 *
 * These tests can not access the database nor files. Calling any Drupal
 * function that needs the database will throw exceptions. These include
 * watchdog(), function_exists(), module_implements(),
 * module_invoke_all() etc.
 */
class DrupalUnitTestCase extends DrupalTestCase {

  /**
   * Constructor for DrupalUnitTestCase.
   */
  function __construct($test_id = NULL) {
    parent::__construct($test_id);
    $this->skipClasses[__CLASS__] = TRUE;
  }

  function setUp() {
    global $db_prefix, $conf;

    // Store necessary current values before switching to prefixed database.
    $this->originalPrefix = $db_prefix;
    $this->originalFileDirectory = file_directory_path();

    // Generate temporary prefixed database to ensure that tests have a clean starting point.
//    $db_prefix = Database::getConnection()->prefixTables('{simpletest' . mt_rand(1000, 1000000) . '}');
    $db_prefix = $db_prefix . 'simpletest' . mt_rand(1000, 1000000);
//    $conf['file_public_path'] = $this->originalFileDirectory . '/' . $db_prefix;
    $conf['file_directory_path'] = $this->originalFileDirectory . '/simpletest/' . substr($db_prefix, 10);

    // If locale is enabled then t() will try to access the database and
    // subsequently will fail as the database is not accessible.
    $module_list = module_list();
    if (isset($module_list['locale'])) {
      $this->originalModuleList = $module_list;
      unset($module_list['locale']);
      module_list(TRUE, FALSE, FALSE, $module_list);
    }
  }

  function tearDown() {
    global $db_prefix, $conf;
    if (preg_match('/simpletest\d+/', $db_prefix)) {
//      $conf['file_public_path'] = $this->originalFileDirectory;
      $conf['file_directory_path'] = $this->originalFileDirectory;
      // Return the database prefix to the original.
      $db_prefix = $this->originalPrefix;
      // Restore modules if necessary.
      if (isset($this->originalModuleList)) {
        module_list(TRUE, FALSE, FALSE, $this->originalModuleList);
      }
    }
  }
}

/**
 * Test case for typical Drupal tests.
 */
class DrupalWebTestCase extends DrupalTestCase {
  /**
   * The URL currently loaded in the internal browser.
   *
   * @var string
   */
  protected $url;

  /**
   * The handle of the current cURL connection.
   *
   * @var resource
   */
  protected $curlHandle;

  /**
   * The headers of the page currently loaded in the internal browser.
   *
   * @var Array
   */
  protected $headers;

  /**
   * The content of the page currently loaded in the internal browser.
   *
   * @var string
   */
  protected $content;

  /**
   * The content of the page currently loaded in the internal browser (plain text version).
   *
   * @var string
   */
  protected $plainTextContent;

  /**
   * The parsed version of the page.
   *
   * @var SimpleXMLElement
   */
  protected $elements = NULL;

  /**
   * The current user logged in using the internal browser.
   *
   * @var bool
   */
  protected $loggedInUser = FALSE;

  /**
   * The current cookie file used by cURL.
   *
   * We do not reuse the cookies in further runs, so we do not need a file
   * but we still need cookie handling, so we set the jar to NULL.
   */
  protected $cookieFile = NULL;

  /**
   * Additional cURL options.
   *
   * DrupalWebTestCase itself never sets this but always obeys what is set.
   */
  protected $additionalCurlOptions = array();

  /**
   * The original user, before it was changed to a clean uid = 1 for testing purposes.
   *
   * @var object
   */
  protected $originalUser = NULL;

  /**
   * HTTP authentication credentials (<username>:<password>).
   */
  protected $httpauth_credentials = NULL;

  /**
   * The current session name, if available.
   */
  protected $session_name = NULL;

  /**
   * The current session ID, if available.
   */
  protected $session_id = NULL;

  /**
   * Constructor for DrupalWebTestCase.
   */
  function __construct($test_id = NULL) {
    parent::__construct($test_id);
    $this->skipClasses[__CLASS__] = TRUE;
  }

  /**
   * Get a node from the database based on its title.
   *
   * @param title
   *   A node title, usually generated by $this->randomName().
   *
   * @return
   *   A node object matching $title.
   */
  function drupalGetNodeByTitle($title) {
//    $nodes = node_load_multiple(array(), array('title' => $title));
//    // Load the first node returned from the database.
//    $returned_node = reset($nodes);
//    return $returned_node;
    return node_load(array('title' => $title));
  }

  /**
   * Creates a node based on default settings.
   *
   * @param $settings
   *   An associative array of settings to change from the defaults, keys are
   *   node properties, for example 'title' => 'Hello, world!'.
   * @return
   *   Created node object.
   */
  protected function drupalCreateNode($settings = array()) {
    // Populate defaults array.
    $settings += array(
//      'body'      => array(FIELD_LANGUAGE_NONE => array(array())),
      'body'      => $this->randomName(32),
      'title'     => $this->randomName(8),
      'comment'   => 2,
//      'changed'   => REQUEST_TIME,
      'changed'   => time(),
      'format'    => FILTER_FORMAT_DEFAULT,
      'moderate'  => 0,
      'promote'   => 0,
      'revision'  => 1,
      'log'       => '',
      'status'    => 1,
      'sticky'    => 0,
      'type'      => 'page',
      'revisions' => NULL,
      'taxonomy'  => NULL,
    );

    // Use the original node's created time for existing nodes.
    if (isset($settings['created']) && !isset($settings['date'])) {
      $settings['date'] = format_date($settings['created'], 'custom', 'Y-m-d H:i:s O');
    }

    // If the node's user uid is not specified manually, use the currently
    // logged in user if available, or else the user running the test.
    if (!isset($settings['uid'])) {
      if ($this->loggedInUser) {
        $settings['uid'] = $this->loggedInUser->uid;
      }
      else {
        global $user;
        $settings['uid'] = $user->uid;
      }
    }

//    // Merge body field value and format separately.
//    $body = array(
//      'value' => $this->randomName(32),
//      'format' => FILTER_FORMAT_DEFAULT
//    );
//    $settings['body'][FIELD_LANGUAGE_NONE][0] += $body;

    $node = (object) $settings;
    node_save($node);

    // Small hack to link revisions to our test user.
//    db_update('node_revision')
//      ->fields(array('uid' => $node->uid))
//      ->condition('vid', $node->vid)
//      ->execute();
    db_query('UPDATE {node_revisions} SET uid = %d WHERE vid = %d', $node->uid, $node->vid);
    return $node;
  }

  /**
   * Creates a custom content type based on default settings.
   *
   * @param $settings
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   * @return
   *   Created content type.
   */
  protected function drupalCreateContentType($settings = array()) {
    // Find a non-existent random type name.
    do {
//      $name = strtolower($this->randomName(8));
//    } while (node_type_get_type($name));
      $name = strtolower($this->randomName(3, 'type_'));
    } while (node_get_types('type', $name));

    // Populate defaults array.
    $defaults = array(
      'type' => $name,
      'name' => $name,
      'description' => '',
      'help' => '',
      'min_word_count' => 0, // Drupal 6.
      'title_label' => 'Title',
      'body_label' => 'Body',
      'has_title' => 1,
      'has_body' => 1,
    );
    // Imposed values for a custom type.
    $forced = array(
      'orig_type' => '',
      'old_type' => '',
      'module' => 'node',
      'custom' => 1,
      'modified' => 1,
      'locked' => 0,
    );
    $type = $forced + $settings + $defaults;
    $type = (object)$type;

    $saved_type = node_type_save($type);
    node_types_rebuild();
    menu_rebuild(); // Drupal 6.

    $this->assertEqual($saved_type, SAVED_NEW, t('Created content type %type.', array('%type' => $type->type)));

    // Reset permissions so that permissions for this content type are available.
    $this->checkPermissions(array(), TRUE);

    return $type;
  }

  /**
   * Get a list files that can be used in tests.
   *
   * @param $type
   *   File type, possible values: 'binary', 'html', 'image', 'javascript', 'php', 'sql', 'text'.
   * @param $size
   *   File size in bytes to match. Please check the tests/files folder.
   * @return
   *   List of files that match filter.
   */
  protected function drupalGetTestFiles($type, $size = NULL) {
    $files = array();

    // Make sure type is valid.
    if (in_array($type, array('binary', 'html', 'image', 'javascript', 'php', 'sql', 'text'))) {
      // Use original file directory instead of one created during setUp().
      $path = $this->originalFileDirectory . '/simpletest';
//      $files = file_scan_directory($path, '/' . $type . '\-.*/');
      $files = file_scan_directory($path, '' . $type . '\-.*');

      // If size is set then remove any files that are not of that size.
      if ($size !== NULL) {
        foreach ($files as $file) {
//          $stats = stat($file->uri);
          $stats = stat($file->filename);
          if ($stats['size'] != $size) {
//            unset($files[$file->uri]);
            unset($files[$file->filename]);
          }
        }
      }
    }
    usort($files, array($this, 'drupalCompareFiles'));
    return $files;
  }

  /**
   * Compare two files based on size and file name.
   */
  protected function drupalCompareFiles($file1, $file2) {
//    $compare_size = filesize($file1->uri) - filesize($file2->uri);
    $compare_size = filesize($file1->filename) - filesize($file2->filename);
    if ($compare_size) {
      // Sort by file size.
      return $compare_size;
    }
    else {
      // The files were the same size, so sort alphabetically.
      return strnatcmp($file1->name, $file2->name);
    }
  }

  /**
   * Create a user with a given set of permissions. The permissions correspond to the
   * names given on the privileges page.
   *
   * @param $permissions
   *   Array of permission names to assign to user.
   * @return
   *   A fully loaded user object with pass_raw property, or FALSE if account
   *   creation fails.
   */
  protected function drupalCreateUser($permissions = array('access comments', 'access content', 'post comments', 'post comments without approval')) {
    // Create a role with the given permission set.
    if (!($rid = $this->drupalCreateRole($permissions))) {
      return FALSE;
    }

    // Create a user assigned to that role.
    $edit = array();
    $edit['name']   = $this->randomName();
    $edit['mail']   = $edit['name'] . '@example.com';
    $edit['roles']  = array($rid => $rid);
    $edit['pass']   = user_password();
    $edit['status'] = 1;

    $account = user_save('', $edit);

    $this->assertTrue(!empty($account->uid), t('User created with name %name and pass %pass', array('%name' => $edit['name'], '%pass' => $edit['pass'])), t('User login'));
    if (empty($account->uid)) {
      return FALSE;
    }

    // Add the raw password so that we can log in as this user.
    $account->pass_raw = $edit['pass'];
    return $account;
  }

  /**
   * Internal helper function; Create a role with specified permissions.
   *
   * @param $permissions
   *   Array of permission names to assign to role.
   * @param $name
   *   (optional) String for the name of the role.  Defaults to a random string.
   * @return
   *   Role ID of newly created role, or FALSE if role creation failed.
   */
  protected function drupalCreateRole(array $permissions, $name = NULL) {
    // Generate random name if it was not passed.
    if (!$name) {
      $name = $this->randomName();
    }

    // Check the all the permissions strings are valid.
    if (!$this->checkPermissions($permissions)) {
      return FALSE;
    }

    // Create new role.
//    $role = new stdClass();
//    $role->name = $name;
//    user_role_save($role);
//    user_role_set_permissions($role->name, $permissions);
    db_query("INSERT INTO {role} (name) VALUES ('%s')", $name);
    $role = db_fetch_object(db_query("SELECT * FROM {role} WHERE name = '%s'", $name));

    $this->assertTrue(isset($role->rid), t('Created role of name: @name, id: @rid', array('@name' => $name, '@rid' => (isset($role->rid) ? $role->rid : t('-n/a-')))), t('Role'));
    if ($role && !empty($role->rid)) {
//      $count = db_query('SELECT COUNT(*) FROM {role_permission} WHERE rid = :rid', array(':rid' => $role->rid))->fetchField();
//      $this->assertTrue($count == count($permissions), t('Created permissions: @perms', array('@perms' => implode(', ', $permissions))), t('Role'));

      // Assign permissions to role and mark it for clean-up.
      db_query("INSERT INTO {permission} (rid, perm) VALUES (%d, '%s')", $role->rid, implode(', ', $permissions));
      $perm = db_result(db_query("SELECT perm FROM {permission} WHERE rid = %d", $role->rid));
      $this->assertTrue(count(explode(', ', $perm)) == count($permissions), t('Created permissions: @perms', array('@perms' => implode(', ', $permissions))), t('Role'));
      return $role->rid;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Check to make sure that the array of permissions are valid.
   *
   * @param $permissions
   *   Permissions to check.
   * @param $reset
   *   Reset cached available permissions.
   * @return
   *   TRUE or FALSE depending on whether the permissions are valid.
   */
  protected function checkPermissions(array $permissions, $reset = FALSE) {
//    $available = &drupal_static(__FUNCTION__);
    static $available;

    if (!isset($available) || $reset) {
//      $available = array_keys(module_invoke_all('permission'));
      $available = module_invoke_all('perm');
    }

    $valid = TRUE;
    foreach ($permissions as $permission) {
      if (!in_array($permission, $available)) {
        $this->fail(t('Invalid permission %permission.', array('%permission' => $permission)), t('Role'));
        $valid = FALSE;
      }
    }
    return $valid;
  }

  /**
   * Log in a user with the internal browser.
   *
   * If a user is already logged in, then the current user is logged out before
   * logging in the specified user.
   *
   * Please note that neither the global $user nor the passed in user object is
   * populated with data of the logged in user. If you need full access to the
   * user object after logging in, it must be updated manually. If you also need
   * access to the plain-text password of the user (set by drupalCreateUser()),
   * e.g. to login the same user again, then it must be re-assigned manually.
   * For example:
   * @code
   *   // Create a user.
   *   $account = $this->drupalCreateUser(array());
   *   $this->drupalLogin($account);
   *   // Load real user object.
   *   $pass_raw = $account->pass_raw;
   *   $account = user_load($account->uid);
   *   $account->pass_raw = $pass_raw;
   * @endcode
   *
   * @param $user
   *   User object representing the user to login.
   *
   * @see drupalCreateUser()
   */
  protected function drupalLogin(stdClass $user) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $edit = array(
      'name' => $user->name,
      'pass' => $user->pass_raw
    );
    $this->drupalPost('user', $edit, t('Log in'));

    // If a "log out" link appears on the page, it is almost certainly because
    // the login was successful.
    $pass = $this->assertLink(t('Log out'), 0, t('User %name successfully logged in.', array('%name' => $user->name)), t('User login'));

    if ($pass) {
      $this->loggedInUser = $user;
    }
  }

  /**
   * Generate a token for the currently logged in user.
   */
  protected function drupalGetToken($value = '') {
    $private_key = drupal_get_private_key();
    return md5($this->session_id . $value . $private_key);
  }

  /*
   * Logs a user out of the internal browser, then check the login page to confirm logout.
   */
  protected function drupalLogout() {
    // Make a request to the logout page, and redirect to the user page, the
    // idea being if you were properly logged out you should be seeing a login
    // screen.
//    $this->drupalGet('user/logout', array('query' => 'destination=user'));
    $this->drupalGet('logout', array('query' => 'destination=user'));
    $pass = $this->assertField('name', t('Username field found.'), t('Logout'));
    $pass = $pass && $this->assertField('pass', t('Password field found.'), t('Logout'));

    if ($pass) {
      $this->loggedInUser = FALSE;
    }
  }

  /**
   * Generates a random database prefix, runs the install scripts on the
   * prefixed database and enable the specified modules. After installation
   * many caches are flushed and the internal browser is setup so that the
   * page requests will run on the new prefix. A temporary files directory
   * is created with the same name as the database prefix.
   *
   * @param ...
   *   List of modules to enable for the duration of the test.
   */
  protected function setUp() {
    global $db_prefix, $user, $language;

    // Store necessary current values before switching to prefixed database.
    $this->originalLanguage = $language;
//    $this->originalLanguageDefault = variable_get('language_default');
    $this->originalPrefix = $db_prefix;
    $this->originalFileDirectory = file_directory_path();
//    $this->originalProfile = drupal_get_profile();
    $clean_url_original = variable_get('clean_url', 0);

    // Must reset locale here, since schema calls t(). (Drupal 6)
    if (module_exists('locale')) {
      $language = (object) array('language' => 'en', 'name' => 'English', 'native' => 'English', 'direction' => 0, 'enabled' => 1, 'plurals' => 0, 'formula' => '', 'domain' => '', 'prefix' => '', 'weight' => 0, 'javascript' => '');
      locale(NULL, NULL, TRUE);
    }

    // Generate temporary prefixed database to ensure that tests have a clean starting point.
//    $db_prefix_new = Database::getConnection()->prefixTables('{simpletest' . mt_rand(1000, 1000000) . '}');
    $db_prefix_new = $db_prefix . 'simpletest' . mt_rand(1000, 1000000);

    // Workaround to insure we init the theme layer before going into prefixed
    // environment. (Drupal 6)
    $this->pass(t('Starting run with db_prefix %prefix', array('%prefix' => $db_prefix_new)), 'System');

//    db_update('simpletest_test_id')
//      ->fields(array('last_prefix' => $db_prefix_new))
//      ->condition('test_id', $this->testId)
//      ->execute();
    db_query("UPDATE {simpletest_test_id}
              SET last_prefix = '%s'
              WHERE test_id = %d", $db_prefix_new, $this->testId);
    $db_prefix = $db_prefix_new;

    // Create test directory ahead of installation so fatal errors and debug
    // information can be logged during installation process.
    $directory = $this->originalFileDirectory . '/simpletest/' . substr($db_prefix, 10);
//    file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    file_check_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

    // Log fatal errors.
    ini_set('log_errors', 1);
    ini_set('error_log', $directory . '/error.log');

//    include_once DRUPAL_ROOT . '/includes/install.inc';
    include_once './includes/install.inc';
    drupal_install_system();

//    $this->preloadRegistry();

//    // Include the default profile
//    variable_set('install_profile', 'default');
//    $profile_details = install_profile_info('default', 'en');

    // Add the specified modules to the list of modules in the default profile.
    // Install the modules specified by the default profile.
//    drupal_install_modules($profile_details['dependencies'], TRUE);
    drupal_install_modules(drupal_verify_profile('default', 'en'));

//    node_type_clear();

    // Install additional modules one at a time in order to make sure that the
    // list of modules is updated between each module's installation.
    $modules = func_get_args();
    foreach ($modules as $module) {
//      drupal_install_modules(array($module), TRUE);
      drupal_install_modules(array($module));
    }

    // Because the schema is static cached, we need to flush
    // it between each run. If we don't, then it will contain
    // stale data for the previous run's database prefix and all
    // calls to it will fail.
    drupal_get_schema(NULL, TRUE);

    // Run default profile tasks.
//    $install_state = array();
//    drupal_install_modules(array('default'), TRUE);
    $task = 'profile';
    default_profile_tasks($task, '');

    // Rebuild caches.
//    node_types_rebuild();
    actions_synchronize();
    _drupal_flush_css_js();
    $this->refreshVariables();
    $this->checkPermissions(array(), TRUE);
    user_access(NULL, NULL, TRUE); // Drupal 6.

    // Log in with a clean $user.
    $this->originalUser = $user;
//    drupal_save_session(FALSE);
//    $user = user_load(1);
    session_save_session(FALSE);
    $user = user_load(array('uid' => 1));

    // Restore necessary variables.
    variable_set('install_profile', 'default');
//    variable_set('install_task', 'done');
    variable_set('install_task', 'profile-finished');
    variable_set('clean_url', $clean_url_original);
    variable_set('site_mail', 'simpletest@example.com');
//    // Set up English language.
//    unset($GLOBALS['conf']['language_default']);
//    $language = language_default();

    // Use the test mail class instead of the default mail handler class.
//    variable_set('mail_sending_system', array('default-system' => 'TestingMailSystem'));
    variable_set('smtp_library', drupal_get_path('module', 'simpletest') . '/simpletest.mail.inc');

    // Use temporary files directory with the same prefix as the database.
//    $public_files_directory  = $this->originalFileDirectory . '/' . $db_prefix;
//    $private_files_directory = $public_files_directory . '/private';
    $directory = $this->originalFileDirectory . '/' . $db_prefix;

    // Set path variables
//    variable_set('file_public_path', $public_files_directory);
//    variable_set('file_private_path', $private_files_directory);
    variable_set('file_directory_path', $directory);

    // Create the directories
//    $directory = file_directory_path('public');
//    file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
//    file_prepare_directory($private_files_directory, FILE_CREATE_DIRECTORY);
    file_check_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

//    drupal_set_time_limit($this->timeLimit);
    set_time_limit($this->timeLimit);
  }

//  /**
//   * This method is called by DrupalWebTestCase::setUp, and preloads the
//   * registry from the testing site to cut down on the time it takes to
//   * setup a clean environment for the current test run.
//   */
//  protected function preloadRegistry() {
//    db_query('INSERT INTO {registry} SELECT * FROM ' . $this->originalPrefix . 'registry');
//    db_query('INSERT INTO {registry_file} SELECT * FROM ' . $this->originalPrefix . 'registry_file');
//  }

  /**
   * Refresh the in-memory set of variables. Useful after a page request is made
   * that changes a variable in a different thread.
   *
   * In other words calling a settings page with $this->drupalPost() with a changed
   * value would update a variable to reflect that change, but in the thread that
   * made the call (thread running the test) the changed variable would not be
   * picked up.
   *
   * This method clears the variables cache and loads a fresh copy from the database
   * to ensure that the most up-to-date set of variables is loaded.
   */
  protected function refreshVariables() {
    global $conf;
    cache_clear_all('variables', 'cache');
//    $conf = variable_initialize();
    $conf = variable_init();
  }

  /**
   * Delete created files and temporary files directory, delete the tables created by setUp(),
   * and reset the database prefix.
   */
  protected function tearDown() {
    global $db_prefix, $user, $language;

    // In case a fatal error occured that was not in the test process read the
    // log to pick up any fatal errors.
    $db_prefix_temp = $db_prefix;
    $db_prefix = $this->originalPrefix;
    simpletest_log_read($this->testId, $db_prefix, get_class($this), TRUE);
    $db_prefix = $db_prefix_temp;

    $emailCount = count(variable_get('drupal_test_email_collector', array()));
    if ($emailCount) {
      $message = format_plural($emailCount, t('!count e-mail was sent during this test.'), t('!count e-mails were sent during this test.'), array('!count' => $emailCount));
      $this->pass($message, t('E-mail'));
    }

    if (preg_match('/simpletest\d+/', $db_prefix)) {
      // Delete temporary files directory.
//      file_unmanaged_delete_recursive(file_directory_path());
      simpletest_clean_temporary_directory(file_directory_path());

      // Remove all prefixed tables (all the tables in the schema).
      $schema = drupal_get_schema(NULL, TRUE);
      $ret = array();
      foreach ($schema as $name => $table) {
        db_drop_table($ret, $name);
      }

      // Return the database prefix to the original.
      $db_prefix = $this->originalPrefix;

      // Return the user to the original one.
      $user = $this->originalUser;
//      drupal_save_session(TRUE);
      session_save_session(TRUE);

      // Bring back default language. (Drupal 6)
      if (module_exists('locale')) {
        drupal_init_language();
        locale(NULL, NULL, TRUE);
      }

      // Ensure that internal logged in variable and cURL options are reset.
      $this->loggedInUser = FALSE;
      $this->additionalCurlOptions = array();

      // Reload module list and implementations to ensure that test module hooks
      // aren't called after tests.
      module_list(TRUE);
//      module_implements('', FALSE, TRUE);
      module_implements('', '', TRUE);

      // Reset the Field API.
//      field_cache_clear();

      // Rebuild caches.
      $this->refreshVariables();

//      // Reset language.
//      $language = $this->originalLanguage;
//      if ($this->originalLanguageDefault) {
//        $GLOBALS['conf']['language_default'] = $this->originalLanguageDefault;
//      }

      // Close the CURL handler.
      $this->curlClose();
    }
  }

  /**
   * Initializes the cURL connection.
   *
   * If the simpletest_httpauth_credentials variable is set, this function will
   * add HTTP authentication headers. This is necessary for testing sites that
   * are protected by login credentials from public access.
   * See the description of $curl_options for other options.
   */
  protected function curlInitialize() {
    global $base_url, $db_prefix;

    if (!isset($this->curlHandle)) {
      $this->curlHandle = curl_init();
      $curl_options = $this->additionalCurlOptions + array(
        CURLOPT_COOKIEJAR => $this->cookieFile,
        CURLOPT_URL => $base_url,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYPEER => FALSE, // Required to make the tests run on https.
        CURLOPT_SSL_VERIFYHOST => FALSE, // Required to make the tests run on https.
        CURLOPT_HEADERFUNCTION => array(&$this, 'curlHeaderCallback'),
      );
      if (isset($this->httpauth_credentials)) {
        $curl_options[CURLOPT_USERPWD] = $this->httpauth_credentials;
      }
      curl_setopt_array($this->curlHandle, $this->additionalCurlOptions + $curl_options);

      // By default, the child session name should be the same as the parent.
      $this->session_name = session_name();
    }
    // We set the user agent header on each request so as to use the current
    // time and a new uniqid.
    if (preg_match('/simpletest\d+/', $db_prefix, $matches)) {
      curl_setopt($this->curlHandle, CURLOPT_USERAGENT, drupal_generate_test_ua($matches[0]));
    }
  }

  /**
   * Performs a cURL exec with the specified options after calling curlConnect().
   *
   * @param $curl_options
   *   Custom cURL options.
   * @return
   *   Content returned from the exec.
   */
  protected function curlExec($curl_options) {
    $this->curlInitialize();
    $url = empty($curl_options[CURLOPT_URL]) ? curl_getinfo($this->curlHandle, CURLINFO_EFFECTIVE_URL) : $curl_options[CURLOPT_URL];
    if (!empty($curl_options[CURLOPT_POST])) {
      // This is a fix for the Curl library to prevent Expect: 100-continue
      // headers in POST requests, that may cause unexpected HTTP response
      // codes from some webservers (like lighttpd that returns a 417 error
      // code). It is done by setting an empty "Expect" header field that is
      // not overwritten by Curl.
      $curl_options[CURLOPT_HTTPHEADER][] = 'Expect:';
    }
    curl_setopt_array($this->curlHandle, $this->additionalCurlOptions + $curl_options);

    // Reset headers and the session ID.
    $this->session_id = NULL;
    $this->headers = array();

    $this->drupalSetContent(curl_exec($this->curlHandle), curl_getinfo($this->curlHandle, CURLINFO_EFFECTIVE_URL));
    $message_vars = array(
      '!method' => !empty($curl_options[CURLOPT_NOBODY]) ? 'HEAD' : (empty($curl_options[CURLOPT_POSTFIELDS]) ? 'GET' : 'POST'),
      '@url' => $url,
      '@status' => curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE),
      '!length' => format_size(strlen($this->content))
    );
    $message = t('!method @url returned @status (!length).', $message_vars);
    $this->assertTrue($this->content !== FALSE, $message, t('Browser'));
    return $this->drupalGetContent();
  }

  /**
   * Reads headers and registers errors received from the tested site.
   *
   * @see _drupal_log_error().
   *
   * @param $curlHandler
   *   The cURL handler.
   * @param $header
   *   An header.
   */
  protected function curlHeaderCallback($curlHandler, $header) {
    $this->headers[] = $header;

    // Errors are being sent via X-Drupal-Assertion-* headers,
    // generated by _drupal_log_error() in the exact form required
    // by DrupalWebTestCase::error().
    if (preg_match('/^X-Drupal-Assertion-[0-9]+: (.*)$/', $header, $matches)) {
      // Call DrupalWebTestCase::error() with the parameters from the header.
      call_user_func_array(array(&$this, 'error'), unserialize(urldecode($matches[1])));
    }

    // Save the session cookie, if set.
    if (preg_match('/^Set-Cookie: ' . preg_quote($this->session_name) . '=([a-z90-9]+)/', $header, $matches)) {
      if ($matches[1] != 'deleted') {
        $this->session_id = $matches[1];
      }
      else {
        $this->session_id = NULL;
      }
    }

    // This is required by cURL.
    return strlen($header);
  }

  /**
   * Close the cURL handler and unset the handler.
   */
  protected function curlClose() {
    if (isset($this->curlHandle)) {
      curl_close($this->curlHandle);
      unset($this->curlHandle);
    }
  }

  /**
   * Parse content returned from curlExec using DOM and SimpleXML.
   *
   * @return
   *   A SimpleXMLElement or FALSE on failure.
   */
  protected function parse() {
    if (!$this->elements) {
      // DOM can load HTML soup. But, HTML soup can throw warnings, suppress
      // them.
      @$htmlDom = DOMDocument::loadHTML($this->content);
      if ($htmlDom) {
        $this->pass(t('Valid HTML found on "@path"', array('@path' => $this->getUrl())), t('Browser'));
        // It's much easier to work with simplexml than DOM, luckily enough
        // we can just simply import our DOM tree.
        $this->elements = simplexml_import_dom($htmlDom);
      }
    }
    if (!$this->elements) {
      $this->fail(t('Parsed page successfully.'), t('Browser'));
    }

    return $this->elements;
  }

  /**
   * Retrieves a Drupal path or an absolute path.
   *
   * @param $path
   *   Drupal path or URL to load into internal browser
   * @param $options
   *   Options to be forwarded to url().
   * @param $headers
   *   An array containing additional HTTP request headers, each formatted as
   *   "name: value".
   * @return
   *   The retrieved HTML string, also available as $this->drupalGetContent()
   */
  protected function drupalGet($path, array $options = array(), array $headers = array()) {
    $options['absolute'] = TRUE;

    // We re-using a CURL connection here. If that connection still has certain
    // options set, it might change the GET into a POST. Make sure we clear out
    // previous options.
    $out = $this->curlExec(array(CURLOPT_HTTPGET => TRUE, CURLOPT_URL => url($path, $options), CURLOPT_NOBODY => FALSE, CURLOPT_HTTPHEADER => $headers));
    $this->refreshVariables(); // Ensure that any changes to variables in the other thread are picked up.

    // Replace original page output with new output from redirected page(s).
    if (($new = $this->checkForMetaRefresh())) {
      $out = $new;
    }
    $this->verbose('GET request to: ' . $path .
                   '<hr />Ending URL: ' . $this->getUrl() .
                   '<hr />' . $out);
    return $out;
  }

  /**
   * Execute a POST request on a Drupal page.
   * It will be done as usual POST request with SimpleBrowser.
   *
   * @param $path
   *   Location of the post form. Either a Drupal path or an absolute path or
   *   NULL to post to the current page. For multi-stage forms you can set the
   *   path to NULL and have it post to the last received page. Example:
   *
   *   // First step in form.
   *   $edit = array(...);
   *   $this->drupalPost('some_url', $edit, t('Save'));
   *
   *   // Second step in form.
   *   $edit = array(...);
   *   $this->drupalPost(NULL, $edit, t('Save'));
   * @param  $edit
   *   Field data in an associative array. Changes the current input fields
   *   (where possible) to the values indicated. A checkbox can be set to
   *   TRUE to be checked and FALSE to be unchecked. Note that when a form
   *   contains file upload fields, other fields cannot start with the '@'
   *   character.
   *
   *   Multiple select fields can be set using name[] and setting each of the
   *   possible values. Example:
   *   $edit = array();
   *   $edit['name[]'] = array('value1', 'value2');
   * @param $submit
   *   Value of the submit button.
   * @param $options
   *   Options to be forwarded to url().
   * @param $headers
   *   An array containing additional HTTP request headers, each formatted as
   *   "name: value".
   */
  protected function drupalPost($path, $edit, $submit, array $options = array(), array $headers = array()) {
    $submit_matches = FALSE;
    if (isset($path)) {
      $html = $this->drupalGet($path, $options);
    }
    if ($this->parse()) {
      $edit_save = $edit;
      // Let's iterate over all the forms.
      $forms = $this->xpath('//form');
      foreach ($forms as $form) {
        // We try to set the fields of this form as specified in $edit.
        $edit = $edit_save;
        $post = array();
        $upload = array();
        $submit_matches = $this->handleForm($post, $edit, $upload, $submit, $form);
        $action = isset($form['action']) ? $this->getAbsoluteUrl($form['action']) : $this->getUrl();

        // We post only if we managed to handle every field in edit and the
        // submit button matches.
        if (!$edit && $submit_matches) {
          $post_array = $post;
          if ($upload) {
            // TODO: cURL handles file uploads for us, but the implementation
            // is broken. This is a less than elegant workaround. Alternatives
            // are being explored at #253506.
            foreach ($upload as $key => $file) {
//              $file = drupal_realpath($file);
              $file = realpath($file);
              if ($file && is_file($file)) {
                $post[$key] = '@' . $file;
              }
            }
          }
          else {
            foreach ($post as $key => $value) {
              // Encode according to application/x-www-form-urlencoded
              // Both names and values needs to be urlencoded, according to
              // http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
              $post[$key] = urlencode($key) . '=' . urlencode($value);
            }
            $post = implode('&', $post);
          }
          $out = $this->curlExec(array(CURLOPT_URL => $action, CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_HTTPHEADER => $headers));
          // Ensure that any changes to variables in the other thread are picked up.
          $this->refreshVariables();

          // Replace original page output with new output from redirected page(s).
          if (($new = $this->checkForMetaRefresh())) {
            $out = $new;
          }
          $this->verbose('POST request to: ' . $path .
                         '<hr />Ending URL: ' . $this->getUrl() .
                         '<hr />Fields: ' . highlight_string('<?php ' . var_export($post_array, TRUE), TRUE) .
                         '<hr />' . $out);
          return $out;
        }
      }
      // We have not found a form which contained all fields of $edit.
      foreach ($edit as $name => $value) {
        $this->fail(t('Failed to set field @name to @value', array('@name' => $name, '@value' => $value)));
      }
      $this->assertTrue($submit_matches, t('Found the @submit button', array('@submit' => $submit)));
      $this->fail(t('Found the requested form fields at @path', array('@path' => $path)));
    }
  }

  /**
   * Check for meta refresh tag and if found call drupalGet() recursively. This
   * function looks for the http-equiv attribute to be set to "Refresh"
   * and is case-sensitive.
   *
   * @return
   *   Either the new page content or FALSE.
   */
  protected function checkForMetaRefresh() {
    if ($this->drupalGetContent() != '' && $this->parse()) {
      $refresh = $this->xpath('//meta[@http-equiv="Refresh"]');
      if (!empty($refresh)) {
        // Parse the content attribute of the meta tag for the format:
        // "[delay]: URL=[page_to_redirect_to]".
        if (preg_match('/\d+;\s*URL=(?P<url>.*)/i', $refresh[0]['content'], $match)) {
          return $this->drupalGet($this->getAbsoluteUrl(decode_entities($match['url'])));
        }
      }
    }
    return FALSE;
  }

  /**
   * Retrieves only the headers for a Drupal path or an absolute path.
   *
   * @param $path
   *   Drupal path or URL to load into internal browser
   * @param $options
   *   Options to be forwarded to url().
   * @param $headers
   *   An array containing additional HTTP request headers, each formatted as
   *   "name: value".
   * @return
   *   The retrieved headers, also available as $this->drupalGetContent()
   */
  protected function drupalHead($path, array $options = array(), array $headers = array()) {
    $options['absolute'] = TRUE;
    $out = $this->curlExec(array(CURLOPT_NOBODY => TRUE, CURLOPT_URL => url($path, $options), CURLOPT_HTTPHEADER => $headers));
    $this->refreshVariables(); // Ensure that any changes to variables in the other thread are picked up.
    return $out;
  }

  /**
   * Handle form input related to drupalPost(). Ensure that the specified fields
   * exist and attempt to create POST data in the correct manner for the particular
   * field type.
   *
   * @param $post
   *   Reference to array of post values.
   * @param $edit
   *   Reference to array of edit values to be checked against the form.
   * @param $submit
   *   Form submit button value.
   * @param $form
   *   Array of form elements.
   * @return
   *   Submit value matches a valid submit input in the form.
   */
  protected function handleForm(&$post, &$edit, &$upload, $submit, $form) {
    // Retrieve the form elements.
    $elements = $form->xpath('.//input|.//textarea|.//select');
    $submit_matches = FALSE;
    foreach ($elements as $element) {
      // SimpleXML objects need string casting all the time.
      $name = (string) $element['name'];
      // This can either be the type of <input> or the name of the tag itself
      // for <select> or <textarea>.
      $type = isset($element['type']) ? (string)$element['type'] : $element->getName();
      $value = isset($element['value']) ? (string)$element['value'] : '';
      $done = FALSE;
      if (isset($edit[$name])) {
        switch ($type) {
          case 'text':
          case 'textarea':
          case 'password':
            $post[$name] = $edit[$name];
            unset($edit[$name]);
            break;
          case 'radio':
            if ($edit[$name] == $value) {
              $post[$name] = $edit[$name];
              unset($edit[$name]);
            }
            break;
          case 'checkbox':
            // To prevent checkbox from being checked.pass in a FALSE,
            // otherwise the checkbox will be set to its value regardless
            // of $edit.
            if ($edit[$name] === FALSE) {
              unset($edit[$name]);
              continue 2;
            }
            else {
              unset($edit[$name]);
              $post[$name] = $value;
            }
            break;
          case 'select':
            $new_value = $edit[$name];
            $index = 0;
            $key = preg_replace('/\[\]$/', '', $name);
            $options = $this->getAllOptions($element);
            foreach ($options as $option) {
              if (is_array($new_value)) {
                $option_value= (string)$option['value'];
                if (in_array($option_value, $new_value)) {
                  $post[$key . '[' . $index++ . ']'] = $option_value;
                  $done = TRUE;
                  unset($edit[$name]);
                }
              }
              elseif ($new_value == $option['value']) {
                $post[$name] = $new_value;
                unset($edit[$name]);
                $done = TRUE;
              }
            }
            break;
          case 'file':
            $upload[$name] = $edit[$name];
            unset($edit[$name]);
            break;
        }
      }
      if (!isset($post[$name]) && !$done) {
        switch ($type) {
          case 'textarea':
            $post[$name] = (string)$element;
            break;
          case 'select':
            $single = empty($element['multiple']);
            $first = TRUE;
            $index = 0;
            $key = preg_replace('/\[\]$/', '', $name);
            $options = $this->getAllOptions($element);
            foreach ($options as $option) {
              // For single select, we load the first option, if there is a
              // selected option that will overwrite it later.
              if ($option['selected'] || ($first && $single)) {
                $first = FALSE;
                if ($single) {
                  $post[$name] = (string)$option['value'];
                }
                else {
                  $post[$key . '[' . $index++ . ']'] = (string)$option['value'];
                }
              }
            }
            break;
          case 'file':
            break;
          case 'submit':
          case 'image':
            if ($submit == $value) {
              $post[$name] = $value;
              $submit_matches = TRUE;
            }
            break;
          case 'radio':
          case 'checkbox':
            if (!isset($element['checked'])) {
              break;
            }
            // Deliberate no break.
          default:
            $post[$name] = $value;
        }
      }
    }
    return $submit_matches;
  }

  /**
   * Perform an xpath search on the contents of the internal browser. The search
   * is relative to the root element (HTML tag normally) of the page.
   *
   * @param $xpath
   *   The xpath string to use in the search.
   * @return
   *   The return value of the xpath search. For details on the xpath string
   *   format and return values see the SimpleXML documentation,
   *   http://us.php.net/manual/function.simplexml-element-xpath.php.
   */
  protected function xpath($xpath) {
    if ($this->parse()) {
      return $this->elements->xpath($xpath);
    }
    return FALSE;
  }

  /**
   * Get all option elements, including nested options, in a select.
   *
   * @param $element
   *   The element for which to get the options.
   * @return
   *   Option elements in select.
   */
  protected function getAllOptions(SimpleXMLElement $element) {
    $options = array();
    // Add all options items.
    foreach ($element->option as $option) {
      $options[] = $option;
    }

    // Search option group children.
    if (isset($element->optgroup)) {
      foreach ($element->optgroup as $group) {
        $options = array_merge($options, $this->getAllOptions($group));
      }
    }
    return $options;
  }

  /**
   * Pass if a link with the specified label is found, and optional with the
   * specified index.
   *
   * @param $label
   *   Text between the anchor tags.
   * @param $index
   *   Link position counting from zero.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertLink($label, $index = 0, $message = '', $group = 'Other') {
    $links = $this->xpath('//a[text()="' . $label . '"]');
    $message = ($message ?  $message : t('Link with label "!label" found.', array('!label' => $label)));
    return $this->assert(isset($links[$index]), $message, $group);
  }

  /**
   * Pass if a link with the specified label is not found.
   *
   * @param $label
   *   Text between the anchor tags.
   * @param $index
   *   Link position counting from zero.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNoLink($label, $message = '', $group = 'Other') {
    $links = $this->xpath('//a[text()="' . $label . '"]');
    $message = ($message ?  $message : t('Link with label "!label" not found.', array('!label' => $label)));
    return $this->assert(empty($links), $message, $group);
  }

  /**
   * Follows a link by name.
   *
   * Will click the first link found with this link text by default, or a
   * later one if an index is given. Match is case insensitive with
   * normalized space. The label is translated label. There is an assert
   * for successful click.
   *
   * @param $label
   *   Text between the anchor tags.
   * @param $index
   *   Link position counting from zero.
   * @return
   *   Page on success, or FALSE on failure.
   */
  protected function clickLink($label, $index = 0) {
    $url_before = $this->getUrl();
    $urls = $this->xpath('//a[text()="' . $label . '"]');

    if (isset($urls[$index])) {
      $url_target = $this->getAbsoluteUrl($urls[$index]['href']);
    }

    $this->assertTrue(isset($urls[$index]), t('Clicked link "!label" (!url_target) from !url_before', array('!label' => $label, '!url_target' => $url_target, '!url_before' => $url_before)), t('Browser'));

    if (isset($urls[$index])) {
      return $this->drupalGet($url_target);
    }
    return FALSE;
  }

  /**
   * Takes a path and returns an absolute path.
   *
   * @param $path
   *   The path, can be a Drupal path or a site-relative path. It might have a
   *   query, too. Can even be an absolute path which is just passed through.
   * @return
   *   An absolute path.
   */
  protected function getAbsoluteUrl($path) {
    $options = array('absolute' => TRUE);
    $parts = parse_url($path);
    // This is more crude than the menu_is_external but enough here.
    if (empty($parts['host'])) {
      $path = $parts['path'];
      $base_path = base_path();
      $n = strlen($base_path);
      if (substr($path, 0, $n) == $base_path) {
        $path = substr($path, $n);
      }
      if (isset($parts['query'])) {
        $options['query'] = $parts['query'];
      }
      $path = url($path, $options);
    }
    return $path;
  }

  /**
   * Get the current url from the cURL handler.
   *
   * @return
   *   The current url.
   */
  protected function getUrl() {
    return $this->url;
  }

  /**
   * Gets the HTTP response headers of the requested page. Normally we are only
   * interested in the headers returned by the last request. However, if a page
   * is redirected or HTTP authentication is in use, multiple requests will be
   * required to retrieve the page. Headers from all requests may be requested
   * by passing TRUE to this function.
   *
   * @param $all_requests
   *   Boolean value specifying whether to return headers from all requests
   *   instead of just the last request. Defaults to FALSE.
   * @return
   *   A name/value array if headers from only the last request are requested.
   *   If headers from all requests are requested, an array of name/value
   *   arrays, one for each request.
   *
   *   The pseudonym ":status" is used for the HTTP status line.
   *
   *   Values for duplicate headers are stored as a single comma-separated list.
   */
  protected function drupalGetHeaders($all_requests = FALSE) {
    $request = 0;
    $headers = array($request => array());
    foreach ($this->headers as $header) {
      $header = trim($header);
      if ($header === '') {
        $request++;
      }
      else {
        if (strpos($header, 'HTTP/') === 0) {
          $name = ':status';
          $value = $header;
        }
        else {
          list($name, $value) = explode(':', $header, 2);
          $name = strtolower($name);
        }
        if (isset($headers[$request][$name])) {
          $headers[$request][$name] .= ',' . trim($value);
        }
        else {
          $headers[$request][$name] = trim($value);
        }
      }
    }
    if (!$all_requests) {
      $headers = array_pop($headers);
    }
    return $headers;
  }

  /**
   * Gets the value of an HTTP response header. If multiple requests were
   * required to retrieve the page, only the headers from the last request will
   * be checked by default. However, if TRUE is passed as the second argument,
   * all requests will be processed from last to first until the header is
   * found.
   *
   * @param $name
   *   The name of the header to retrieve. Names are case-insensitive (see RFC
   *   2616 section 4.2).
   * @param $all_requests
   *   Boolean value specifying whether to check all requests if the header is
   *   not found in the last request. Defaults to FALSE.
   * @return
   *   The HTTP header value or FALSE if not found.
   */
  protected function drupalGetHeader($name, $all_requests = FALSE) {
    $name = strtolower($name);
    $header = FALSE;
    if ($all_requests) {
      foreach (array_reverse($this->drupalGetHeaders(TRUE)) as $headers) {
        if (isset($headers[$name])) {
          $header = $headers[$name];
          break;
        }
      }
    }
    else {
      $headers = $this->drupalGetHeaders();
      if (isset($headers[$name])) {
        $header = $headers[$name];
      }
    }
    return $header;
  }

  /**
   * Gets the current raw HTML of requested page.
   */
  protected function drupalGetContent() {
    return $this->content;
  }

  /**
   * Gets an array containing all e-mails sent during this test case.
   *
   * @param $filter
   *   An array containing key/value pairs used to filter the e-mails that are returned.
   * @return
   *   An array containing e-mail messages captured during the current test.
   */
  protected function drupalGetMails($filter = array()) {
    $captured_emails = variable_get('drupal_test_email_collector', array());
    $filtered_emails = array();

    foreach ($captured_emails as $message) {
      foreach ($filter as $key => $value) {
//        if (!isset($message[$key]) || $message[$key] != $value) {
        if (!isset($message['params'][$key]) || $message['params'][$key] != $value) {
          continue 2;
        }
      }
      $filtered_emails[] = $message;
    }

    return $filtered_emails;
  }

  /**
   * Sets the raw HTML content. This can be useful when a page has been fetched
   * outside of the internal browser and assertions need to be made on the
   * returned page.
   *
   * A good example would be when testing drupal_http_request(). After fetching
   * the page the content can be set and page elements can be checked to ensure
   * that the function worked properly.
   */
  protected function drupalSetContent($content, $url = 'internal:') {
    $this->content = $content;
    $this->url = $url;
    $this->plainTextContent = FALSE;
    $this->elements = FALSE;
  }

  /**
   * Pass if the raw text IS found on the loaded page, fail otherwise. Raw text
   * refers to the raw HTML that the page generated.
   *
   * @param $raw
   *   Raw (HTML) string to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertRaw($raw, $message = '', $group = 'Other') {
    if (!$message) {
      $message = t('Raw "@raw" found', array('@raw' => check_plain($raw)));
    }
    return $this->assert(strpos($this->content, $raw) !== FALSE, $message, $group);
  }

  /**
   * Pass if the raw text is NOT found on the loaded page, fail otherwise. Raw text
   * refers to the raw HTML that the page generated.
   *
   * @param $raw
   *   Raw (HTML) string to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoRaw($raw, $message = '', $group = 'Other') {
    if (!$message) {
      $message = t('Raw "@raw" not found', array('@raw' => check_plain($raw)));
    }
    return $this->assert(strpos($this->content, $raw) === FALSE, $message, $group);
  }

  /**
   * Pass if the text IS found on the text version of the page. The text version
   * is the equivalent of what a user would see when viewing through a web browser.
   * In other words the HTML has been filtered out of the contents.
   *
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertText($text, $message = '', $group = 'Other') {
    return $this->assertTextHelper($text, $message, $group, FALSE);
  }

  /**
   * Pass if the text is NOT found on the text version of the page. The text version
   * is the equivalent of what a user would see when viewing through a web browser.
   * In other words the HTML has been filtered out of the contents.
   *
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoText($text, $message = '', $group = 'Other') {
    return $this->assertTextHelper($text, $message, $group, TRUE);
  }

  /**
   * Helper for assertText and assertNoText.
   *
   * It is not recommended to call this function directly.
   *
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @param $not_exists
   *   TRUE if this text should not exist, FALSE if it should.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertTextHelper($text, $message, $group, $not_exists) {
    if ($this->plainTextContent === FALSE) {
      $this->plainTextContent = filter_xss($this->content, array());
    }
    if (!$message) {
      $message = !$not_exists ? t('"@text" found', array('@text' => $text)) : t('"@text" not found', array('@text' => $text));
    }
    return $this->assert($not_exists == (strpos($this->plainTextContent, $text) === FALSE), $message, $group);
  }

  /**
   * Pass if the text is found ONLY ONCE on the text version of the page.
   *
   * The text version is the equivalent of what a user would see when viewing
   * through a web browser. In other words the HTML has been filtered out of
   * the contents.
   *
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertUniqueText($text, $message = '', $group = 'Other') {
    return $this->assertUniqueTextHelper($text, $message, $group, TRUE);
  }

  /**
   * Pass if the text is found MORE THAN ONCE on the text version of the page.
   *
   * The text version is the equivalent of what a user would see when viewing
   * through a web browser. In other words the HTML has been filtered out of
   * the contents.
   *
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoUniqueText($text, $message = '', $group = 'Other') {
    return $this->assertUniqueTextHelper($text, $message, $group, FALSE);
  }

  /**
   * Helper for assertUniqueText and assertNoUniqueText.
   *
   * It is not recommended to call this function directly.
   *
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @param $be_unique
   *   TRUE if this text should be found only once, FALSE if it should be found more than once.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertUniqueTextHelper($text, $message, $group, $be_unique) {
    if ($this->plainTextContent === FALSE) {
      $this->plainTextContent = filter_xss($this->content, array());
    }
    if (!$message) {
      $message = '"' . $text . '"' . ($be_unique ? ' found only once' : ' found more than once');
    }
    $first_occurance = strpos($this->plainTextContent, $text);
    if ($first_occurance === FALSE) {
      return $this->assert(FALSE, $message, $group);
    }
    $offset = $first_occurance + strlen($text);
    $second_occurance = strpos($this->plainTextContent, $text, $offset);
    return $this->assert($be_unique == ($second_occurance === FALSE), $message, $group);
  }

  /**
   * Will trigger a pass if the Perl regex pattern is found in the raw content.
   *
   * @param $pattern
   *   Perl regex to look for including the regex delimiters.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertPattern($pattern, $message = '', $group = 'Other') {
    if (!$message) {
      $message = t('Pattern "@pattern" found', array('@pattern' => $pattern));
    }
    return $this->assert((bool) preg_match($pattern, $this->drupalGetContent()), $message, $group);
  }

  /**
   * Will trigger a pass if the perl regex pattern is not present in raw content.
   *
   * @param $pattern
   *   Perl regex to look for including the regex delimiters.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoPattern($pattern, $message = '', $group = 'Other') {
    if (!$message) {
      $message = t('Pattern "@pattern" not found', array('@pattern' => $pattern));
    }
    return $this->assert(!preg_match($pattern, $this->drupalGetContent()), $message, $group);
  }

  /**
   * Pass if the page title is the given string.
   *
   * @param $title
   *   The string the title should be.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertTitle($title, $message, $group = 'Other') {
    return $this->assertEqual(current($this->xpath('//title')), $title, $message, $group);
  }

  /**
   * Pass if the page title is not the given string.
   *
   * @param $title
   *   The string the title should not be.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoTitle($title, $message, $group = 'Other') {
    return $this->assertNotEqual(current($this->xpath('//title')), $title, $message, $group);
  }

  /**
   * Assert that a field exists in the current page by the given XPath.
   *
   * @param $xpath
   *   XPath used to find the field.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertFieldByXPath($xpath, $value, $message, $group = 'Other') {
    $fields = $this->xpath($xpath);

    // If value specified then check array for match.
    $found = TRUE;
    if ($value) {
      $found = FALSE;
      if ($fields) {
        foreach ($fields as $field) {
          if (isset($field['value']) && $field['value'] == $value) {
            // Input element with correct value.
            $found = TRUE;
          }
          elseif (isset($field->option)) {
            // Select element found.
            if ($this->getSelectedItem($field) == $value) {
              $found = TRUE;
            }
            else {
              // No item selected so use first item.
              $items = $this->getAllOptions($field);
              if (!empty($items) && $items[0]['value'] == $value) {
                $found = TRUE;
              }
            }
          }
          elseif ((string) $field == $value) {
            // Text area with correct text.
            $found = TRUE;
          }
        }
      }
    }
    return $this->assertTrue($fields && $found, $message, $group);
  }

  /**
   * Get the selected value from a select field.
   *
   * @param $element
   *   SimpleXMLElement select element.
   * @return
   *   The selected value or FALSE.
   */
  protected function getSelectedItem(SimpleXMLElement $element) {
    foreach ($element->children() as $item) {
      if (isset($item['selected'])) {
        return $item['value'];
      }
      elseif ($item->getName() == 'optgroup') {
        if ($value = $this->getSelectedItem($item)) {
          return $value;
        }
      }
    }
    return FALSE;
  }

  /**
   * Assert that a field does not exist in the current page by the given XPath.
   *
   * @param $xpath
   *   XPath used to find the field.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoFieldByXPath($xpath, $value, $message, $group = 'Other') {
    $fields = $this->xpath($xpath);

    // If value specified then check array for match.
    $found = TRUE;
    if ($value) {
      $found = FALSE;
      if ($fields) {
        foreach ($fields as $field) {
          if ($field['value'] == $value) {
            $found = TRUE;
          }
        }
      }
    }
    return $this->assertFalse($fields && $found, $message, $group);
  }

  /**
   * Assert that a field exists in the current page with the given name and value.
   *
   * @param $name
   *   Name of field to assert.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertFieldByName($name, $value = '', $message = '') {
    return $this->assertFieldByXPath($this->constructFieldXpath('name', $name), $value, $message ? $message : t('Found field by name @name', array('@name' => $name)), t('Browser'));
  }

  /**
   * Assert that a field does not exist with the given name and value.
   *
   * @param $name
   *   Name of field to assert.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoFieldByName($name, $value = '', $message = '') {
    return $this->assertNoFieldByXPath($this->constructFieldXpath('name', $name), $value, $message ? $message : t('Did not find field by name @name', array('@name' => $name)), t('Browser'));
  }

  /**
   * Assert that a field exists in the current page with the given id and value.
   *
   * @param $id
   *   Id of field to assert.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertFieldById($id, $value = '', $message = '') {
    return $this->assertFieldByXPath($this->constructFieldXpath('id', $id), $value, $message ? $message : t('Found field by id @id', array('@id' => $id)), t('Browser'));
  }

  /**
   * Assert that a field does not exist with the given id and value.
   *
   * @param $id
   *   Id of field to assert.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoFieldById($id, $value = '', $message = '') {
    return $this->assertNoFieldByXPath($this->constructFieldXpath('id', $id), $value, $message ? $message : t('Did not find field by id @id', array('@id' => $id)), t('Browser'));
  }

  /**
   * Assert that a checkbox field in the current page is checked.
   *
   * @param $id
   *   Id of field to assert.
   * @param $message
   *   Message to display.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertFieldChecked($id, $message = '') {
    $elements = $this->xpath('//input[@id="' . $id . '"]');
    return $this->assertTrue(isset($elements[0]) && !empty($elements[0]['checked']), $message ? $message : t('Checkbox field @id is checked.', array('@id' => $id)), t('Browser'));
  }

  /**
   * Assert that a checkbox field in the current page is not checked.
   *
   * @param $id
   *   Id of field to assert.
   * @param $message
   *   Message to display.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoFieldChecked($id, $message = '') {
    $elements = $this->xpath('//input[@id="' . $id . '"]');
    return $this->assertTrue(isset($elements[0]) && empty($elements[0]['checked']), $message ? $message : t('Checkbox field @id is not checked.', array('@id' => $id)), t('Browser'));
  }

  /**
   * Assert that a field exists with the given name or id.
   *
   * @param $field
   *   Name or id of field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertField($field, $message = '', $group = 'Other') {
    return $this->assertFieldByXPath($this->constructFieldXpath('name', $field) . '|' . $this->constructFieldXpath('id', $field), '', $message, $group);
  }

  /**
   * Assert that a field does not exist with the given name or id.
   *
   * @param $field
   *   Name or id of field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoField($field, $message = '', $group = 'Other') {
    return $this->assertNoFieldByXPath($this->constructFieldXpath('name', $field) . '|' . $this->constructFieldXpath('id', $field), '', $message, $group);
  }

  /**
   * Helper function: construct an XPath for the given set of attributes and value.
   *
   * @param $attribute
   *   Field attributes.
   * @param $value
   *   Value of field.
   * @return
   *   XPath for specified values.
   */
  protected function constructFieldXpath($attribute, $value) {
    return '//textarea[@' . $attribute . '="' . $value . '"]|//input[@' . $attribute . '="' . $value . '"]|//select[@' . $attribute . '="' . $value . '"]';
  }

  /**
   * Assert the page responds with the specified response code.
   *
   * @param $code
   *   Response code. For example 200 is a successful page request. For a list
   *   of all codes see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html.
   * @param $message
   *   Message to display.
   * @return
   *   Assertion result.
   */
  protected function assertResponse($code, $message = '') {
    $curl_code = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    $match = is_array($code) ? in_array($curl_code, $code) : $curl_code == $code;
    return $this->assertTrue($match, $message ? $message : t('HTTP response expected !code, actual !curl_code', array('!code' => $code, '!curl_code' => $curl_code)), t('Browser'));
  }

  /**
   * Assert that the most recently sent e-mail message has a field with the given value.
   *
   * @param $name
   *   Name of field or message property to assert. Examples: subject, body, id, ...
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertMail($name, $value = '', $message = '') {
    $captured_emails = variable_get('drupal_test_email_collector', array());
    $email = end($captured_emails);
//    return $this->assertTrue($email && isset($email[$name]) && $email[$name] == $value, $message, t('E-mail'));
    return $this->assertTrue(
      ($email && isset($email[$name]) && $email[$name] == $value) ||
      ($email && isset($email['params'][$name]) && $email['params'][$name] == $value),
      $message,
      t('E-mail'));
  }

  /**
   * Log verbose message in a text file.
   *
   * The a link to the vebose message will be placed in the test results via
   * as a passing assertion with the text '[verbose message]'.
   *
   * @param $message
   *   The verbose message to be stored.
   * @see simpletest_verbose()
   */
  protected function verbose($message) {
    if ($id = simpletest_verbose($message)) {
      $this->pass(l(t('Verbose message'), $this->originalFileDirectory . '/simpletest/verbose/' . get_class($this) . '-' . $id . '.html', array('attributes' => array('target' => '_blank'))), 'Debug');
    }
  }

}

/**
 * Log verbose message in a text file.
 *
 * If verbose mode is enabled then page requests will be dumped to a file and
 * presented on the test result screen. The messages will be placed in a file
 * located in the simpletest directory in the original file system.
 *
 * @param $message
 *   The verbose message to be stored.
 * @param $original_file_directory
 *   The original file directory, before it was changed for testing purposes.
 * @param $test_class
 *   The active test case class.
 * @return
 *   The ID of the message to be placed in related assertion messages.
 * @see DrupalTestCase->originalFileDirectory
 * @see DrupalWebTestCase->verbose()
 */
function simpletest_verbose($message, $original_file_directory = NULL, $test_class = NULL) {
  static $file_directory = NULL, $class = NULL, $id = 1;
//  $verbose = &drupal_static(__FUNCTION__);
  static $verbose;

  // Will pass first time during setup phase, and when verbose is TRUE.
  if (!isset($original_file_directory) && !$verbose) {
    return FALSE;
  }

  if ($message && $file_directory) {
    $message = '<hr />ID #' . $id . ' (<a href="' . $class . '-' . ($id - 1) . '.html">Previous</a> | <a href="' . $class . '-' . ($id + 1) . '.html">Next</a>)<hr />' . $message;
    file_put_contents($file_directory . "/simpletest/verbose/$class-$id.html", $message, FILE_APPEND);
    return $id++;
  }

  if ($original_file_directory) {
    $file_directory = $original_file_directory;
    $class = $test_class;
    $verbose = variable_get('simpletest_verbose', FALSE);
    $directory = $file_directory . '/simpletest/verbose';
//    return file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    return file_check_directory($directory, FILE_CREATE_DIRECTORY);
  }
  return FALSE;
}
