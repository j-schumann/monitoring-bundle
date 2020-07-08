# Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

## Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require vrok/monitoring-bundle
```

## Applications that don't use Symfony Flex
### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require vrok/monitoring-bundle
```

### Step 2: Enable the Bundle

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

## Usage

Call `bin/console monitor:send-alive-message` from console, best triggered via
a cron every 30min etc.
The sent messages can then be monitored & purged via [Nagios/Icinga plugin](http://buhacoff.net/software/check_email_delivery/check_imap_receive.html)

/usr/lib/nagios/plugins/contrib/check_service_alive:
```shell script
#!/bin/bash
# Check if the Symfony application "My-App-Name" sent an email:
# --imap-retries - search only once instead of 10x in 5s intervals
# -s YOUNGER -s 3600 = within the last hour
# -s SUBJECT -s "My-App-Name is alive" - only with this subject
/usr/lib/nagios/plugins/check_imap_receive -H mail.domain.tld -U receiver@domain.tld -P imap_password --tls -w 5 -c 10 --imap-retries 1 --search-critical-min 1 -s YOUNGER -s 3600 -s SUBJECT -s "My-App-Name is alive" --nodelete
```

/usr/lib/nagios/plugins/contrib/purge_alive_messages:
```shell script
#!/bin/bash
# As above, but without limitation to the last hour: Delete all but the last "alive" message
# --capture-max "timestamp (\d+)" - extract nummeric value
# --capture-min thisdoesnotexist - required to _not_ capture a minimum message, that would not be deleted otherwise 
# --no-delete-captured - we want to delete old messages so the postbox doesn't fill (--delete is enabled by default)
#   but we want to keep the last message so the Nagios check does not fail if he runs e.g. every 15min
#   so with --capture-max and --no-delete-captured we keep the last mail, delete the rest (of emails matching the search)
/usr/lib/nagios/plugins/check_imap_receive -H mail.domain.tld -U receiver@domain.tld -P imap_password --tls -w 5 -c 10 --imap-retries 1 --search-critical-min 1 -s SUBJECT -s "My-App-Name is alive" --capture-max "timestamp (\d+)" --capture-min thisdoesnotexist --nodelete-captured
```

Obviously replace "My-App-Name" with the _app_name_ your configured in the
`packages/vrok_monitoring.yaml` and the mailserver domain, receiver address &
password with your values.