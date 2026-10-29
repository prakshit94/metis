# Storefront registration failure

The live storefront log recorded `OwenIt\Auditing\Exceptions\AuditingException: Invalid UserResolver implementation` while creating the CRM customer in `StorefrontController::register`. The surrounding transaction rolled back before the shopping account could be created.

The separate application's Composer autoloader used the parent `vendor` folder, but Laravel package discovery searched `storefront/vendor`. Its package manifest was therefore empty. The shared customer model's audit observer ran without the auditing package's resolver configuration. The earlier test suite disabled audit/activity logging and did not reproduce this live configuration.

Fixed `storefront/bootstrap/app.php` to point Laravel's package discovery manifest at the same installed dependencies as the autoloader, retaining the storefront's own application/cache paths. Rebuilt the storefront package manifest locally. Audit and activity service providers now register normally; logging is not disabled to make registration work.

Updated the integration runner to enable audit and activity logging, enable auditing for console-based tests, assert that the resolver implements the audit contract, and verify that public registration actually records the customer's creation audit. All **44 integration checks passed** using a uniquely named disposable MySQL schema and fake outbound mail. The schema is removed after testing. No real customer account was created and no real verification email was sent by this verification run.

The actual `/shop/register` page also loads correctly. Its password rules, verification requirement and customer/staff separation remain unchanged. The storefront README now includes package rediscovery after shared dependency updates.
