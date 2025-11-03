CREATE USER 'restricted_user'@'%' IDENTIFIED BY 'password';
GRANT SELECT, INSERT, UPDATE, DELETE ON klugesmanagement_sql.* TO 'restricted_user'@'%';
FLUSH PRIVILEGES;