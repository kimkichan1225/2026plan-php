#!/usr/bin/env node

/**
 * Railway 데이터베이스 자동 설정 스크립트
 *
 * 사용법:
 * 1. npm install mysql2 (최초 1회)
 * 2. railway login (최초 1회)
 * 3. railway link (최초 1회, MySQL 서비스 선택)
 * 4. node railway-setup.js
 */

const fs = require('fs');
const path = require('path');

// mysql2 확인
try {
    require.resolve('mysql2');
} catch (e) {
    console.error('❌ mysql2 package not found!');
    console.error('Please run: npm install mysql2');
    process.exit(1);
}

console.log('========================================');
console.log('Railway Database Setup');
console.log('========================================\n');

// schema_simple.sql 파일 확인 (테이블만)
const schemaPath = path.join(__dirname, 'database', 'schema_simple.sql');
if (!fs.existsSync(schemaPath)) {
    console.error('❌ Error: database/schema_simple.sql not found!');
    process.exit(1);
}

console.log('✓ Found schema_simple.sql\n');

// Railway 환경 변수에서 DB 설정 가져오기
// Public URL 사용 (로컬에서 접근 가능)
const dbConfig = {
    host: process.env.RAILWAY_TCP_PROXY_DOMAIN || process.env.MYSQLHOST,
    port: parseInt(process.env.RAILWAY_TCP_PROXY_PORT || process.env.MYSQLPORT) || 3306,
    user: process.env.MYSQLUSER,
    password: process.env.MYSQLPASSWORD,
    database: process.env.MYSQLDATABASE,
    multipleStatements: true
};

// 환경 변수 확인
if (!dbConfig.host || !dbConfig.user || !dbConfig.database) {
    console.error('❌ Error: MySQL environment variables not found!');
    console.error('\nPlease make sure you:');
    console.error('1. Linked to Railway project: railway link');
    console.error('2. Selected MySQL service');
    console.error('\nThen run this script with: railway run node railway-setup.js\n');
    process.exit(1);
}

console.log(`Connecting to: ${dbConfig.host}:${dbConfig.port}`);
console.log(`Database: ${dbConfig.database}\n`);

// SQL 파일 읽기
const sqlContent = fs.readFileSync(schemaPath, 'utf8');

console.log('Executing SQL...\n');

// mysql2를 사용하여 연결 및 실행
(async () => {
    const mysql = require('mysql2/promise');

    try {
        const connection = await mysql.createConnection(dbConfig);
        console.log('✓ Connected to MySQL\n');

        // 전체 SQL을 한 번에 실행 (multipleStatements 사용)
        console.log('Creating tables...\n');

        await connection.query(sqlContent);

        console.log('✓ All tables created\n');

        // 테이블 확인
        const [tables] = await connection.query('SHOW TABLES');
        console.log('Created tables:');
        tables.forEach(table => {
            console.log('  ✓', Object.values(table)[0]);
        });
        console.log('');

        await connection.end();

        console.log('========================================');
        console.log('✅ Database setup complete!');
        console.log('========================================\n');
        console.log('Next steps:');
        console.log('1. Check Railway deployment logs');
        console.log('2. Visit your Railway app URL');
        console.log('3. Login with: test@test.com / test1234\n');

    } catch (error) {
        console.error('\n❌ Error executing SQL:');
        console.error(error.message);
        process.exit(1);
    }
})();
