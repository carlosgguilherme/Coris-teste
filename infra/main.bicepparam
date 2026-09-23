using './main.bicep'

param sufixo = 'carlos'
param mysqlAdminPassword = readEnvironmentVariable('MYSQL_ADMIN_PASSWORD')
param jwtSecret = readEnvironmentVariable('JWT_SECRET')
param adminSenha = readEnvironmentVariable('ADMIN_SENHA')
