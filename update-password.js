#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');

(async () => {
    try {
        const connection = await mysql.createConnection({
            host: process.env.RAILWAY_TCP_PROXY_DOMAIN || process.env.MYSQLHOST,
            port: parseInt(process.env.RAILWAY_TCP_PROXY_PORT || process.env.MYSQLPORT) || 3306,
            user: process.env.MYSQLUSER,
            password: process.env.MYSQLPASSWORD,
            database: process.env.MYSQLDATABASE
        });

        console.log('✓ Connected to MySQL\n');

        // Update password
        const hash = '$2y$10$N9qo8uLOickgx2ZMRZoMye1IVI.9J6WqDqYZ3FqkMqZpVHXVKv6mO';
        const [result] = await connection.query(
            'UPDATE users SET password_hash = ? WHERE email = ?',
            [hash, 'test@test.com']
        );

        console.log('✓ Password updated!');
        console.log(`  Rows affected: ${result.affectedRows}`);
        console.log('\nTest account:');
        console.log('  Email: test@test.com');
        console.log('  Password: test1234\n');

        await connection.end();
    } catch (error) {
        console.error('Error:', error.message);
        process.exit(1);
    }
})();
