-- Run once, via cPanel's phpMyAdmin (or any MySQL client) against your database.
-- Creates the first admin login. Log in at /admin/login, then change this password
-- immediately from a proper "change password" flow once one exists.

INSERT INTO admin_users (full_name, email, password_hash, role)
VALUES (
    'Platform Admin',
    'admin@yourdomain.co.za',
    '$2b$12$UMpn5H5qYe8YYRpYam.FkevQ9nSyxz2LK8K7B1NjVvv8bvSfMZDCq',  -- password: ChangeThisPassword123!
    'super_admin'
);
