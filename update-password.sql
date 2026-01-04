-- Update test user password to 'test1234'
UPDATE users
SET password_hash = '$2y$10$N9qo8uLOickgx2ZMRZoMye1IVI.9J6WqDqYZ3FqkMqZpVHXVKv6mO'
WHERE email = 'test@test.com';
