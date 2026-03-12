# Hostinger Deployment

## Before Upload

1. Copy `.env.example` to `.env`.
2. Fill in your Hostinger database, mail, app URL, and OAuth values.
3. Keep `APP_ENV=production` on Hostinger.
4. Do not upload local-only files:
   `config/mail.local.php`
   `config/oauth.local.php`
   `.git/`
   `.vscode/`

## Database

1. Create an empty MySQL database in Hostinger.
2. Import [database/hostinger-empty-schema.sql](/c:/xampp/htdocs/ZYPP_E-com_Web/zypp_camera_house/database/hostinger-empty-schema.sql).
3. If Hostinger gave you a different database name, update the first two SQL lines before import.

## Hosting Setup

1. Upload the project into `public_html` or your chosen domain root.
2. Ensure these folders are writable by PHP:
   `storage/uploads/admins`
   `storage/uploads/brands`
   `storage/uploads/categories`
   `storage/uploads/contents`
   `storage/uploads/media/thumbnails`
   `storage/uploads/media/videos`
   `storage/uploads/payment-proofs`
   `storage/uploads/products`
   `storage/uploads/usp`
3. Make sure PHP includes:
   `mysqli`
   `pdo_mysql`
   `mbstring`
   `openssl`
   `fileinfo`

## Mail and OAuth

1. Configure SMTP values in `.env`.
2. Update your Google OAuth redirect URI to:
   `https://your-domain.com/auth/oauth_callback.php?provider=google`

## Final Checks

1. Test customer login, admin login, password reset email, product image upload, payment proof upload, and CMS image upload.
2. If you use Tidio, set `TIDIO_PUBLIC_KEY` in `.env`.
