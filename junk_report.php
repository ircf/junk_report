<?php
class junk_report extends rcube_plugin
{
  public $task = 'settings';
  private $rcmail_inst;

  function init()
  {
    $this->rcmail_inst = rcmail::get_instance();
    $this->load_config();
    $this->add_texts('localization/', true);
    $this->require_plugin('markasjunk2');

    $this->register_action('plugin.junk_report', array($this, 'init_html'));
    $this->register_action('plugin.junk_report.show', array($this, 'init_html'));
    $this->register_action('plugin.junk_report.save', array($this, 'save'));
    $this->register_action('plugin.junk_report.not_junk', array($this, 'not_junk'));
    $this->register_action('plugin.junk_report.cron', array($this, 'cron'));

    //$this->api->output->add_handler('junk_report_form', array($this, 'init_form'));
  }

  /**
   * display settings form
   */
  function init_html()
  {
    // TODO
  }

  /**
   * save junk_report settings :
   * $frequency : never, daily, weekly, monthly (default to never)
   * $maxlength : integer (default to 100)
   */
  function save()
  {
    // TODO
  }

  /**
   * authenticate from token
   * mark message as not junk
   * display HTML result
   */
  function not_junk()
  {
    // TODO
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
