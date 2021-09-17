<?php
/**
 * Junk Report
 *
 * Send periodically an email to each mailbox with the list of messages in their junk directory
 *
 * @version 1.0
 * @author IRCF
 * @url https://github.com/ircf/junk_report
 */
class junk_report extends rcube_plugin
{
  public $task = 'mail|settings';
  private $user;
  private $prefs;
  private $rcmail;

  function init()
  {
    $this->rcmail = rcmail::get_instance();
    $this->user = $this->rcmail->user;
    $this->prefs = $this->user->get_prefs();
    $this->load_config();
    $this->add_texts('localization/', true);
    $this->require_plugin('markasjunk2');

    $this->register_action('plugin.junk_report', array($this, 'show'));
    $this->register_action('plugin.junk_report.show', array($this, 'show'));
    $this->register_action('plugin.junk_report.save', array($this, 'save'));
    $this->register_action('plugin.junk_report.not_junk', array($this, 'not_junk'));
    $this->register_action('plugin.junk_report.cron', array($this, 'cron'));

    $this->add_hook('settings_actions', array($this, 'settings_actions'));
  }

  /**
   * add settings tab
   */
  function settings_actions($args)
  {
    $args['actions'][] = array(
      'action' => 'plugin.junk_report.show',
      'class'  => 'junk_report',
      'label'  => 'title',
      'domain' => 'junk_report',
    );
    return $args;
  }

  /**
   * display settings form
   */
  function show()
  {
    $this->register_handler('plugin.body', array($this, 'show_body'));
    $this->rcmail->output->set_pagetitle($this->gettext('title'));
    $this->rcmail->output->send('plugin');
  }

  function show_body()
  {
    //read from user preferences
    if (!(isset($this->prefs["frequency"]) && isset($this->prefs["maxlength"]))) {
      $frequency = $config['default_frequency'];
      $maxlength = $config['default_maxlength'];
    } else {
      $frequency = $this->prefs["frequency"];
      $maxlength = $this->prefs["maxlength"];
    }

    $table = new html_table(array('cols' => 2, 'class' => 'propform'));

    $table->add('title', html::label('', $this->gettext('frequency')));
    $select = new html_select(array('name' => '_frequency'));
    $select->add($this->gettext('never'), 'never');
    $select->add($this->gettext('daily'), 'daily');
    $select->add($this->gettext('weekly'), 'weekly');
    $select->add($this->gettext('monthly'), 'monthly');
    $table->add('', $select->show($frequency));

    $table->add('title', html::label('', $this->gettext('maxlength')));
    $table->add('',  html::tag('input', array(
      'type' => 'number',
      'name' => '_maxlength',
      'value' => $maxlength,
      'min' => 1,
      'max' => $config['default_maxlength'],
      'required' => true
    )));

    return html::tag('form', array(
        'action' => $this->rcmail->url('plugin.junk_report.save'),
        'method' => 'post'
      ),
      html::div(array('class' => 'box formcontent'),
        html::div(array('class' => 'boxtitle'), $this->gettext('title'))
        . html::div(array('class' => 'boxcontent'),
          html::p('', $this->gettext('description'))
          . '<br>'
          . $table->show()
          . html::p(array('class' => 'formbuttons'),
            html::tag('input', array('type' => 'submit',
              'class' => 'button mainaction', 'value' => $this->gettext('save'))
            )
          )
        )
      )
    );
  }

  /**
   * save junk_report settings :
   * $frequency : never, daily, weekly, monthly (default to DEFAULT_FREQUENCY)
   * $maxlength : integer (default to DEFAULT_MAXLENGTH)
   */
  function save()
  {
    $frequency = rcube_utils::get_input_value('_frequency',rcube_utils::INPUT_POST);
    $maxlength = rcube_utils::get_input_value('_maxlength',rcube_utils::INPUT_POST);
    $this->prefs["frequency"] = $frequency;
    $this->prefs["maxlength"] = $maxlength;
    $this->user->save_prefs($this->prefs);
  }

  /**
   * authenticate from token
   * mark message as not junk
   * display HTML result
   */
  function not_junk()
  {
    $this->rcmail->output->include_script('../../plugins/markasjunk2/markasjunk2.js');
    $this->rcmail->output->include_script('../../plugins/junk_report/junk_report.js');
    $this->require_plugin('markasjunk2');
    $this->register_handler('plugin.body', array($this, 'not_junk_body'));
    $this->rcmail->output->set_pagetitle($this->gettext('not_junk'));
    $this->rcmail->output->send('plugin');
  }

  function not_junk_body()
  {
    // TODO authenticate

    //return html::p(array('class' => ''), $this->gettext('auth_failed'));

    // TODO mark as not junk
    $uid = rcube_utils::get_input_value('_uid',rcube_utils::INPUT_GET);

    // V1 : use rcmail : code is executed after load, but list does not work, markasjunk functions are prefixed with "rcmail."
    //$this->rcmail->output->command('command', 'list', "Junk");
    //$this->rcmail->output->set_env('uid', $uid);
    //echo "<script>console.table($temp)</script>";
    //$this->rcmail->output->command('rcmail_markasjunk2_notjunk', '');
    //$this->rcmail->output->command('rcmail_markasjunk2_move','INBOX',$uid);

    // V2 : use api does nothing
    //$this->api->output->command('command', 'list', "Junk");

    // V3 : use external js
    //$this->rcmail->output->command('rcmail_junk_report_move', $uid);

    // V4 : mix php and js
    // This is only working after 3 repeats of clicking the restore link
    // Also, this had to be done on the same page (not the one created) and you don't refresh the page
    // Maybe it could be the cache?

    $this->rcmail->output->set_env('uid', $uid);

    $this->rcmail->output->command('rcmail_junk_report_move', $uid);

    return html::p(array('class' => ''), $this->gettext('not_junk_done'));
  }

  /**
   * scan all $maildir/domain/mailbox/$junkdir
   * check if $frequency matches current date
   * generate a HTML/text report with the last $maxlength messages
   * send an email to the mailbox
   */
  function cron()
  {
    // TODO
  }

}
