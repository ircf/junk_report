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
  const DEFAULT_FREQUENCY = 'never';
  const DEFAULT_MAXLENGTH = 50;
  public $task = 'settings';
  private $rcmail;

  function init()
  {
    $this->rcmail = rcmail::get_instance();
    $this->load_config();
    $this->add_texts('localization/', true);
    $this->require_plugin('markasjunk2');

    $this->register_action('plugin.junk_report', array($this, 'init_html'));
    $this->register_action('plugin.junk_report.show', array($this, 'init_html'));
    $this->register_action('plugin.junk_report.save', array($this, 'save'));
    $this->register_action('plugin.junk_report.not_junk', array($this, 'not_junk'));
    $this->register_action('plugin.junk_report.cron', array($this, 'cron'));

    $this->add_hook('settings_actions', array($this, 'settings_actions'));
  }

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

  function init_html()
  {
    $this->register_handler('plugin.body', array($this, 'show'));
    $this->rcmail->output->set_pagetitle($this->gettext('title'));
    $this->rcmail->output->send('plugin');
  }

  /**
   * display settings form
   */
  function show()
  {
    // TODO read from user preferences
    $frequency = self::DEFAULT_FREQUENCY;
    $maxlength = self::DEFAULT_MAXLENGTH;

    $table = new html_table(array('cols' => 2, 'class' => 'propform'));

    $table->add('title', html::label('', $this->gettext('frequency')));
    $select = new html_select(array('name' => '_frequency'));
    $select->add($this->gettext('never'), 'never');
    $select->add($this->gettext('daily'), 'daily');
    $select->add($this->gettext('weekly'), 'weekly');
    $select->add($this->gettext('monthly'), 'monthly');
    $table->add('', $select->show());

    $table->add('title', html::label('', $this->gettext('maxlength')));
    $table->add('',  html::tag('input', array(
      'type' => 'number',
      'name' => '_maxlength',
      'value' => $maxlength
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
