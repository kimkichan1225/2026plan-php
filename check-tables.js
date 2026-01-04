#!/usr/bin/env node

const mysql = require('mysql2/promise');

const dbConfig = {
    host: process.env.RAILWAY_TCP_PROXY_DOMAIN || process.env.MYSQLHOST,
    port: parseInt(process.env.RAILWAY_TCP_PROXY_PORT || process.env.MYSQLPORT) || 3306,
    user: process.env.MYSQLUSER,
    password: process.env.MYSQLPASSWORD,
    database: process.env.MYSQLDATABASE
};

(async () => {
    try {
        const connection = await mysql.createConnection(dbConfig);
        console.log('✓ Connected to MySQL\n');

        const [tables] = await connection.query('SHOW TABLES');

        console.log('Tables in database:');
        if (tables.length === 0) {
            console.log('  (no tables found)\n');
        } else {
            tables.forEach(table => {
                console.log('  -', Object.values(table)[0]);
            });
            console.log('');
        }

        await connection.end();
    } catch (error) {
        console.error('Error:', error.message);
    }
})();
