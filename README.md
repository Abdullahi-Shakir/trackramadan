# trackramadan

# Al-Azhar Scholarship App — Setup

This small PHP app accepts scholarship applications and emails them (with attachments) to the admissions address.

## Requirements
- PHP 7.4+ (with fileinfo extension enabled)
- Composer (for PHPMailer)
- An SMTP account (recommended) or a properly configured local MTA

## Install PHPMailer

Open PowerShell in the project folder and run:

```powershell
composer require phpmailer/phpmailer
```

## Configuration
- Edit `submit.php` and set the `$smtp_config` values (host, port, username, password, secure).
- Set `$from_email` to a domain you control to improve deliverability.

## Security & privacy notes
- This example stores uploaded files temporarily in `uploads/` and deletes them after sending. In production, use secure storage, virus scanning, and retention policies.
- Ensure you have a privacy policy and consent before collecting personal data (passport numbers, location).
- Validate and sanitize inputs on both client and server; this repository includes basic validation but should be extended for production.

## Testing
- Use the form at `index.html` in a browser; allow location access when prompted.
- Check `email_log.txt` for send attempts and errors.

If you want, I can configure the project to store submissions in a database and implement an admin interface to review applications.
