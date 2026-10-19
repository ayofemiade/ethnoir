# Hostinger Deployment Guide for Ethnoir

This guide provides step-by-step instructions for deploying Ethnoir to **Hostinger Web Hosting / Cloud Hosting**.

---

## Architecture Summary
- **Frontend**: High-performance static HTML, CSS, and JS compiled via Astro into the `dist/` folder.
- **Backend**: Native PHP API handler located at `dist/api/contact.php` that handles contact form submissions.
- **Server Rules**: Pre-configured `dist/.htaccess` for Hostinger's LiteSpeed/Apache web servers (forces HTTPS, manages clean URLs, serves custom `404.html`, enables Gzip compression, and applies browser caching).

---

## Option 1: File Manager Upload (Recommended — Takes 2 Minutes)

We have created an automated bundle command that builds the site and packages all files into a ready-to-upload zip archive.

### Step 1: Generate the Deployment Archive
In your terminal, run:
```bash
npm run bundle:hostinger
```
*(This compiles the latest version of the site and creates `hostinger-deploy.zip` in your project root.)*

### Step 2: Upload to Hostinger
1. Log into your **Hostinger hPanel** ([hpanel.hostinger.com](https://hpanel.hostinger.com)).
2. Go to **Websites** &rarr; click **Manage** next to your domain.
3. In the left sidebar, click **Files** &rarr; **File Manager** (or **File Manager for this website**).
4. Navigate into the **`public_html`** directory.
   *(If there is an existing default `default.php` or placeholder file, you can delete it.)*
5. Click the **Upload** icon (top right) &rarr; select **File** &rarr; choose `hostinger-deploy.zip`.
6. Once uploaded, right-click `hostinger-deploy.zip` and choose **Extract**.
7. In the extract dialog, specify `.` or leave it as `public_html` so the files extract directly into `public_html/`.
8. Once extracted, verify that `index.html`, `.htaccess`, `404.html`, `robots.txt`, and the `api/` folder are inside `public_html/`.
9. Delete `hostinger-deploy.zip` to keep your storage clean.

Your site is now live!

---

## Option 2: GitHub Actions Automated Push-to-Deploy

If you want every `git push` to automatically deploy to Hostinger without manual uploads:

1. In Hostinger hPanel, go to **Files** &rarr; **FTP Accounts**.
2. Note your **FTP Hostname**, **FTP Username**, and create a password.
3. In your GitHub repository, go to **Settings** &rarr; **Secrets and variables** &rarr; **Actions**.
4. Add the following repository secrets:
   - `FTP_SERVER` (e.g. `ftp.ethnoir.com` or your Hostinger server IP)
   - `FTP_USERNAME` (your Hostinger FTP username)
   - `FTP_PASSWORD` (your Hostinger FTP password)
5. A GitHub Actions workflow can then build `dist/` and transfer it directly to `public_html/`.

---

## Contact Form Email Setup on Hostinger

The contact form at `/contact` submits data via `fetch()` to `/api/contact.php`.

### Default Delivery (Zero Configuration Required)
- By default, `/api/contact.php` uses Hostinger's built-in PHP `mail()` function with an envelope sender header (`-f omichael@ethnoirconsulting.com`).
- Emails are immediately delivered to `omichael@ethnoirconsulting.com`.

### Best Practices for Email Deliverability on Hostinger:
1. In Hostinger hPanel, go to **Emails** &rarr; **Email Accounts**.
2. Ensure `omichael@ethnoirconsulting.com` is created on your domain.
3. In **Emails** &rarr; **DNS / DKIM / SPF**, verify that Hostinger's default SPF and DKIM records are active. This prevents contact form inquiries from landing in your spam folder.

### Optional: Authenticated SMTP Delivery
If you prefer authenticated SMTP over native `mail()`:
1. Copy `contact-config.example.php` to `contact-config.php`.
2. Fill in your Hostinger email password:
   ```php
   return [
       'recipient_email' => 'omichael@ethnoirconsulting.com',
       'sender_email'    => 'omichael@ethnoirconsulting.com',
       'smtp_host'       => 'smtp.hostinger.com',
       'smtp_port'       => 465,
       'smtp_user'       => 'omichael@ethnoirconsulting.com',
       'smtp_pass'       => 'YOUR_EMAIL_PASSWORD',
   ];
   ```
3. Upload `contact-config.php` one directory level **above** `public_html/` (e.g., `/home/u123456789/contact-config.php`) so it is never exposed to the public web.

---

## Verification & Post-Launch Checklist

Once deployed to Hostinger, verify the following:

- [ ] **Home Page**: Visit `https://ethnoir.com` &mdash; hero animation, typography, and sections load cleanly.
- [ ] **Navigation Links**: Click `About`, `For Organizations`, `Our Work`, `Check-In`, and `Contact`.
- [ ] **SSL / HTTPS**: Confirm the padlock appears in the address bar.
- [ ] **404 Page**: Visit `https://ethnoir.com/non-existent-page` to confirm the custom 404 page renders.
- [ ] **Contact Form Submission**: Go to `https://ethnoir.com/contact`, fill out a test inquiry, and verify that the confirmation screen displays and you receive the email.
- [ ] **Sitemap & Robots**: Confirm `https://ethnoir.com/robots.txt` and `https://ethnoir.com/sitemap-index.xml` are accessible.
