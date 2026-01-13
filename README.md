
<div align="center">
	<img width="100" src="https://user-images.githubusercontent.com/25181517/186884153-99edc188-e4aa-4c84-91b0-e2df260ebc33.png" alt="Ubuntu" title="Ubuntu"/>
	<img width="100" src="https://github.com/marwin1991/profile-technology-icons/assets/25181517/afcf1c98-544e-41fb-bf44-edba5e62809a" alt="Laravel" title="Laravel"/>
	<img width="100" src="https://user-images.githubusercontent.com/25181517/117448124-a2da9800-af3e-11eb-85d2-bd1b69b65603.png" alt="Vue.js" title="Vue.js"/>
	<img width="100" src="https://user-images.githubusercontent.com/25181517/183345125-9a7cd2e6-6ad6-436f-8490-44c903bef84c.png" alt="Nginx" title="Nginx"/>
	<img width="100" src="https://user-images.githubusercontent.com/25181517/117207330-263ba280-adf4-11eb-9b97-0ac5b40bc3be.png" alt="Docker" title="Docker"/>
	<img width="100" src="https://user-images.githubusercontent.com/25181517/183568594-85e280a7-0d7e-4d1a-9028-c8c2209e073c.png" alt="Node.js" title="Node.js"/>
</div>

# GB App

**GB App** is designed to provide a more efficient and manageable way to visualize Power BI reports using Microsoft's API. This application offers the following features:

- User, role, and permission management
- Report creation, management, and assignment
- Password recovery
- Two-factor authentication

### Technologies Used

- Ubuntu 22.04
- PHP v8.2
- Laravel Framework v10.14
- Node.js v18.16 LTS
- Vue.js v3.2

---

## Installation

### Using Docker in Production

1. Download and unzip the project.
2. Copy the environment variables file:  
   ```bash
   cp .env.example .env
   ```
3. Build the Docker container:  
   ```bash
   docker compose build
   ```
4. Start the container:  
   ```bash
   docker compose up -d
   ```
5. Permissions and Ownership
   Ensure the following permissions and ownership settings are applied:
    
   ```bash
   docker compose exec app chmod -R 775 /var/www/html/storage
   docker compose exec app chmod -R 775 /var/www/html/bootstrap/cache
   docker compose exec app chown -R www-data:www-data /var/www/html/storage
   docker compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
   ```
   
6. Install PHP dependencies:
     
   ```bash
   docker compose exec app composer install
   ```
   
7. Install Node.js dependencies:
     
   ```bash
   docker compose exec app npm install
   ```
   
8. Modify the `.env` file with your environment-specific settings:
    
   ```bash
   DB_CONNECTION=mysql       # mysql or sqlsrv
   DB_HOST=0.0.0.0           # Database server IP
   DB_PORT=3306              # Database server port
   DB_DATABASE=gb-app        # Database name
   DB_USERNAME=root          # Database user
   DB_PASSWORD=password      # Database password
   ```

   ```bash
   POWERBI_USER_ID=          # Azure AD Application ID
   POWERBI_GRANT_TYPE=       # Default: client_credentials
   POWERBI_CLIENT_SECRET=    # Azure AD Client Secret
   POWERBI_CLIENT_ID=        # Azure AD Client ID
   POWERBI_RESOURCE=         # Power BI API endpoint
   ```

9. Clear the application cache (run this each time after modifying the `.env` file):
    
   ```bash
   docker compose exec app php artisan optimize
   ```

10. Run migrations and seed the database (to create the super admin):
      
   ```bash
   docker compose exec app php artisan migrate --seed
   ```

11. Build the JavaScript files:
      
   ```bash
   docker compose exec app npm run build
   ```

---

### Access

- The application is served on port `80`, but you can modify this in the `docker-compose.yml` file.
- Access the application at `http://{SERVER_IP|LOCALHOST}:{PORT}`.


### Troubleshooting

If you encounter errors while building the container during `apt-get update` after adding a repository, try adding the following commands to the dockerfile:

```bash
sudo GNUTLS_CPUID_OVERRIDE=0x1 apt-get update
export GNUTLS_CPUID_OVERRIDE=0x1
```

### Power BI Token Information

Check the available free embed tokens for Power BI using this link:  
[Power BI Available Feature](https://learn.microsoft.com/en-us/rest/api/power-bi/available-features/get-available-feature-by-name#code-try-0)
