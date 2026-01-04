#!/usr/bin/env node

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

        // Check all users
        const [users] = await connection.query('SELECT id, name, email, password_hash, created_at FROM users');

        console.log('Users in database:');
        console.log(JSON.stringify(users, null, 2));

        console.log('\n--- User count:', users.length);

        await connection.end();
    } catch (error) {
        console.error('Error:', error.message);
        process.exit(1);
    }
})();
