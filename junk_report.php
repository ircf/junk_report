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
  const DEFAULT_JUNKDIR = 'Junk'; // TODO config option
  const DEFAULT_FREQUENCY = 'never'; // TODO config option
  const DEFAULT_MAXLENGTH = 100; // TODO config option
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
      $frequency = self::DEFAULT_FREQUENCY;
      $maxlength = self::DEFAULT_MAXLENGTH;
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
    $table->add('', $select->show());

    $table->add('title', html::label('', $this->gettext('maxlength')));
    $table->add('',  html::tag('input', array(
      'type' => 'number',
      'name' => '_maxlength',
      'value' => $maxlength,
      'min' => 1,
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

    //$is_spam = false;
    $multi_folder = false;
    $messageset = rcmail::get_uids();
    //$mbox_name = 'Junk';
    $dest_mbox = 'INBOX';

    $this->_do_salearn($uid,false); 	//This is the function that learns the Bayensian

    //if ($this->rcmail->storage->move_message($uid, 'INBOX', 'Junk')) echo "<script>console.log('moved')</script>";
    //$this->rcmail->output->command('rcmail_markasjunk2', 'not_junk');
    $this->api->output->command('rcmail_markasjunk2_move', $dest_mbox, $this->_messageset_to_uids($messageset, $multi_folder));

    //$this->api->output->command('display_message', $is_spam ? $this->gettext('reportedasjunk') : $this->gettext('reportedasnotjunk'), 'confirmation');
    //$this->api->output->send(); 	//make 404

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

private function _messageset_to_uids($messageset, $multi_folder)
        {
                $a_uids = array();

                foreach ($messageset as $mbox => $uids) {
                        foreach ($uids as $uid) {
                                $a_uids[] = $multi_folder ? $uid . '-' . $mbox : $uid;
                        }
                }

                return $a_uids;
	}

// Maybe make a driver for it, but this function is only used in one situation

private function _do_salearn($uids, $spam)
        {
                $rcmail = rcube::get_instance();
                $temp_dir = realpath($rcmail->config->get('temp_dir'));

                if ($spam)
                        $command = $rcmail->config->get('markasjunk2_spam_cmd');
                else
                        $command = $rcmail->config->get('markasjunk2_ham_cmd');

                if (!$command)
                        return;

                $command = str_replace('%u', $_SESSION['username'], $command);
                $command = str_replace('%l', $rcmail->user->get_username('local'), $command);
                $command = str_replace('%d', $rcmail->user->get_username('domain'), $command);
                if (preg_match('/%i/', $command)) {
                        $identity_arr = $rcmail->user->get_identity();
                        $command = str_replace('%i', $identity_arr['email'], $command);
                }

                foreach ($uids as $uid) {
                        // reset command for next message
                        $tmp_command = $command;

                        // get DSPAM signature from header (if %xds macro is used)
                        if (preg_match('/%xds/', $command)) {
                                if (preg_match('/^X\-DSPAM\-Signature:\s+((\d+,)?([a-f\d]+))\s*$/im', $rcmail->storage->get_raw_headers($uid), $dspam_signature))
                                        $tmp_command = str_replace('%xds', $dspam_signature[1], $tmp_command);
                                else
                                        continue; // no DSPAM signature found in headers -> continue with next uid/message
                        }

		if (preg_match('/%f/', $command)) {
                                $tmpfname = tempnam($temp_dir, 'rcmSALearn');
                                file_put_contents($tmpfname, $rcmail->storage->get_raw_body($uid));
                                $tmp_command = str_replace('%f', $tmpfname, $tmp_command);
                        }

                        exec($tmp_command, $output);

                        if ($rcmail->config->get('markasjunk2_debug')) {
                                rcube::write_log('markasjunk2', $tmp_command);
                                rcube::write_log('markasjunk2', $output);
                        }

                        if (preg_match('/%f/', $command))
                                unlink($tmpfname);

                        $output = '';
                }
        }
}
