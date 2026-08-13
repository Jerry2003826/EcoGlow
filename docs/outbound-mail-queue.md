# Outbound mail queue

Enquiry replies and invoice emails are written to `outbound_messages` with
status `queued`. Nothing is sent in the web request. A console command
consumes the queue.

## Command

```bash
bin/cake send_outbound_messages
bin/cake send_outbound_messages --limit 20
```

Development uses the `Debug` transport (`EMAIL_TRANSPORT` unset). Messages
are rendered to `logs/debug.log` and are not delivered. Production sets
`EMAIL_TRANSPORT=Smtp` and the cPanel mailbox credentials.

## Idempotency

The consumer claims a row with `UPDATE … SET status = 'sending' WHERE id = ?
AND status = 'queued'`. A second run, or a second process, sees zero rows
updated and skips that id. After a successful send the status is `sent`.
Re-running the command never re-sends a `sent` row.

## Retries

Each failed attempt increments `attempt_count` and writes
`outbound_message_events`. The row goes back to `queued` until
`attempt_count` reaches 5, then it is marked `failed` and is left alone.

A row stuck in `sending` for more than 15 minutes is treated as a failed
attempt (process crash) and retried under the same cap.

## Cron

Run once a minute from the application root (adjust the path and PHP
binary for the host):

```cron
* * * * * cd /home/example/fit3047S2 && bin/cake send_outbound_messages >> logs/outbound_mail.log 2>&1
```

On cPanel this is a “Cron Jobs” entry with the same command. The job is
safe to overlap: claim-by-status is the lock.
