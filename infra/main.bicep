targetScope = 'resourceGroup'

@description('Sufixo para gerar nomes únicos (ex.: suas iniciais).')
@minLength(3)
@maxLength(10)
param sufixo string

param location string = resourceGroup().location

@description('Static Web Apps não está disponível em todas as regiões.')
param staticWebAppLocation string = 'eastus2'

param mysqlAdminUser string = 'corisadmin'

@secure()
param mysqlAdminPassword string

@description('URL do frontend liberada no CORS da API. Deixe vazio para usar a URL gerada do Static Web App.')
param corsOrigin string = ''

var nome = 'coris-seguros-${sufixo}'
var databaseName = 'coris_seguros'
var keyVaultSecretsUserRoleId = '4633458b-17de-408a-b874-0445c86b69e6'

resource logAnalytics 'Microsoft.OperationalInsights/workspaces@2022-10-01' = {
  name: 'log-${nome}'
  location: location
  properties: {
    sku: { name: 'PerGB2018' }
    retentionInDays: 30
  }
}

resource appInsights 'Microsoft.Insights/components@2020-02-02' = {
  name: 'appi-${nome}'
  location: location
  kind: 'web'
  properties: {
    Application_Type: 'web'
    WorkspaceResourceId: logAnalytics.id
  }
}

resource mysql 'Microsoft.DBforMySQL/flexibleServers@2023-12-30' = {
  name: 'mysql-${nome}'
  location: location
  sku: {
    name: 'Standard_B1ms'
    tier: 'Burstable'
  }
  properties: {
    version: '8.0.21'
    administratorLogin: mysqlAdminUser
    administratorLoginPassword: mysqlAdminPassword
    storage: { storageSizeGB: 20 }
    backup: {
      backupRetentionDays: 7
      geoRedundantBackup: 'Disabled'
    }
    highAvailability: { mode: 'Disabled' }
  }

  resource database 'databases' = {
    name: databaseName
    properties: {
      charset: 'utf8mb4'
      collation: 'utf8mb4_unicode_ci'
    }
  }

  resource allowAzureServices 'firewallRules' = {
    name: 'AllowAzureServices'
    properties: {
      startIpAddress: '0.0.0.0'
      endIpAddress: '0.0.0.0'
    }
  }
}

resource keyVault 'Microsoft.KeyVault/vaults@2023-07-01' = {
  name: 'kv-coris-${sufixo}'
  location: location
  properties: {
    tenantId: subscription().tenantId
    sku: {
      family: 'A'
      name: 'standard'
    }
    enableRbacAuthorization: true
    enableSoftDelete: true
    softDeleteRetentionInDays: 7
  }

  resource dbPassword 'secrets' = {
    name: 'DbPassword'
    properties: { value: mysqlAdminPassword }
  }
}

resource staticWebApp 'Microsoft.Web/staticSites@2023-01-01' = {
  name: 'swa-${nome}'
  location: staticWebAppLocation
  sku: {
    name: 'Free'
    tier: 'Free'
  }
  properties: {}
}

resource appServicePlan 'Microsoft.Web/serverfarms@2023-01-01' = {
  name: 'plan-${nome}'
  location: location
  kind: 'linux'
  sku: {
    name: 'B1'
    tier: 'Basic'
  }
  properties: { reserved: true }
}

resource api 'Microsoft.Web/sites@2023-01-01' = {
  name: 'api-${nome}'
  location: location
  kind: 'app,linux'
  identity: { type: 'SystemAssigned' }
  properties: {
    serverFarmId: appServicePlan.id
    httpsOnly: true
    siteConfig: {
      linuxFxVersion: 'PHP|8.3'
      appCommandLine: 'bash /home/site/wwwroot/azure/startup.sh'
      alwaysOn: true
      minTlsVersion: '1.2'
      ftpsState: 'Disabled'
      healthCheckPath: '/api/health'
      appSettings: [
        { name: 'APP_DEBUG', value: 'false' }
        { name: 'DB_CONNECTION', value: 'mysql' }
        { name: 'DB_HOST', value: mysql.properties.fullyQualifiedDomainName }
        { name: 'DB_PORT', value: '3306' }
        { name: 'DB_DATABASE', value: databaseName }
        { name: 'DB_USERNAME', value: mysqlAdminUser }
        { name: 'DB_PASSWORD', value: '@Microsoft.KeyVault(SecretUri=${keyVault::dbPassword.properties.secretUri})' }
        { name: 'MYSQL_ATTR_SSL_CA', value: '/etc/ssl/certs/ca-certificates.crt' }
        { name: 'CORS_ALLOWED_ORIGIN', value: empty(corsOrigin) ? 'https://${staticWebApp.properties.defaultHostname}' : corsOrigin }
        { name: 'APPLICATIONINSIGHTS_CONNECTION_STRING', value: appInsights.properties.ConnectionString }
      ]
    }
  }
}

resource apiPodeLerSegredos 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(keyVault.id, api.id, keyVaultSecretsUserRoleId)
  scope: keyVault
  properties: {
    roleDefinitionId: subscriptionResourceId('Microsoft.Authorization/roleDefinitions', keyVaultSecretsUserRoleId)
    principalId: api.identity.principalId
    principalType: 'ServicePrincipal'
  }
}

output apiUrl string = 'https://${api.properties.defaultHostName}'
output frontendUrl string = 'https://${staticWebApp.properties.defaultHostname}'
output apiName string = api.name
output staticWebAppName string = staticWebApp.name
output mysqlHost string = mysql.properties.fullyQualifiedDomainName
