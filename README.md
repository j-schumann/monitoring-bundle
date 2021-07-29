# Monitoring-Bundle

Schedule sending email messages from the console to check if cron is running
and mails can be sent by the system (e.g. your Docker container running the application).
If the Symfony messenger is configured, the messages are pushed to the queue and processed by a worker, so this also
checks if queue & workers are up. 

[![CI Status](https://github.com/j-schumann/monitoring-bundle/actions/workflows/ci.yaml/badge.svg)](https://github.com/j-schumann/monitoring-bundle/actions)
[![Coverage Status](https://coveralls.io/repos/github/j-schumann/monitoring-bundle/badge.svg?branch=master)](https://coveralls.io/github/j-schumann/monitoring-bundle?branch=master)

## Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require vrok/monitoring-bundle
```

### Applications that don't use Symfony Flex
#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require vrok/monitoring-bundle
```

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Vrok\MonitoringBundle\VrokMonitoringBundle::class => ['all' => true],
];
```

## Configuration
The Symfony Mailer must be configured and should set a default sender
(FROM address) via listener / config.

Create `config/packages/vrok_monitoring.yaml`:
```yaml
vrok_monitoring:
  # receiver of the ping email
  monitor_address: mail@domain.tld

  # application name used in the subject and body of the mail
  app_name: My-App-Name
```

Optionally get this options from the ENV with `'%env(MONITOR_ADDRESS)%'` etc. 

## Usage

Call `bin/console monitor:send-alive-message` from console, best triggered via
a cron every 30min etc.:

```
2,32 * * * * www-data /usr/local/bin/php /var/www/html/bin/console monitor:send-alive-message
```

Email subject will be "Service [app_name] is alive!".
The text body contains an integer timestamp which is later used by the Icinga check
to purge all but the newest message): 
```
Automatic message from [app_name]: The service is alive and can send emails
at 2020-09-06T09:02:01+02:00 (timestamp 1599375721)
```

## Icinga configuration

Retrieval of the sent messages requires the [check_imap_receive](http://buhacoff.net/software/check_email_delivery/check_imap_receive.html)
Nagios/Icinga plugin, make sure this is installed and working on a server monitored with Icinga, can be the same as the
application server but doesn't have to.

Create the check script & replace the mailserver domain, receiver address & password with your values, we don't use
arguments for those to not store those credentials on the Icinga master.

`/usr/lib/nagios/plugins/contrib/check_service_alive`:
```shell script
#!/bin/bash
# Delete all but the last "alive" mail
# -s SUBJECT -s "$1" - only with this subject, given in argument $1
# --capture-max "timestamp (\d+)" - extract nummeric value
# --capture-min thisdoesnotexist - required to _not_ capture a minimum message, that would not be deleted otherwise 
# --no-delete-captured - we want to delete old messages so the postbox doesn't fill (--delete is enabled by default)
#   but we want to keep the last message so the Nagios check does not fail if he runs e.g. every 15min
#   so with --capture-max and --no-delete-captured we keep the last mail, delete the rest (of emails matching the search)
# no output (1>/dev/null) so Icinga only reads the status from the check below 
/usr/lib/nagios/plugins/check_imap_receive -H mail.domain.tld -U receiver@domain.tld -P imap_password --tls -w 5 -c 10 --imap-retries 1 --search-critical-min 1 -s SUBJECT -s "$1" --capture-max "timestamp (\d+)" --capture-min thisdoesnotexist --nodelete-captured 1>/dev/null

# Check if a mail with the given subject was received in the last hour:
# --imap-retries - search only once instead of 10x in 5s intervals
# -s YOUNGER -s 3600 = within the last hour
# -s SUBJECT -s "$1" - only with this subject, given in argument $1
/usr/lib/nagios/plugins/check_imap_receive -H mail.domain.tld -U receiver@domain.tld -P imap_password --tls -w 5 -c 10 --imap-retries 1 --search-critical-min 1 -s YOUNGER -s 3600 -s SUBJECT -s "$1" --nodelete
```


Add the command definition in the Icinga master:
```
// Symfony Service Check (a mail with the given subject was received within the last hour)
object CheckCommand "check_service_alive" {
  command = [ PluginDir + "/contrib/check_service_alive" ]
  arguments = {
    "--subject" = {
      value = "$subject$"
      description = "email subject [substring] to search for"
      required = true
      skip_key = true
    }
  }
}
```

Also the service definition:
```
// Symfony Service Check (a mail with the given subject was received within the last hour)
apply Service for (name => subject in host.vars.service_alive) {
  check_command = "check_service_alive"
  check_interval = 30m
  display_name = name + " service check"
  vars.subject = subject
  assign where host.vars.client_endpoint && host.vars.check_service_alive == true
  command_endpoint = host.vars.client_endpoint
}
```

Finally, enable & configure the service in your host definition,
replace "dev.domain.tld" with the _app_name_ you configured in the
`packages/vrok_monitoring.yaml`. You can monitor multiple applications with one _monitor_address_, just make sure the
app_names are different (subject is matched by pattern, so using "domain.tld is alive" and "dev.domain.tld is alive will
collide, prefix with "Service " to prevent this):
```
    vars.check_service_alive = true
    vars.service_alive["App-Dev"] = "dev.domain.tld is alive"
    vars.service_alive["App-Prod"] = "Service domain.tld is alive"
```