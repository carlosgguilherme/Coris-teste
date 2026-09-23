using './main.bicep'

param sufixo = 'carlos'
param mysqlAdminPassword = readEnvironmentVariable('MYSQL_ADMIN_PASSWORD')
