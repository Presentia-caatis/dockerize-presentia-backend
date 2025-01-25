# 🌟 Project Name

Welcome to **Presentia Backend App**! This repository contains a Laravel application (API only) configured with **Laravel Breeze**.

---

## 🚀 Getting Started

Follow these instructions to set up the project on your local machine.

### Prerequisites

Ensure you have the following tools installed:

- [Git](https://git-scm.com/) (to clone the repository)
- [Docker](https://www.docker.com/) and Docker Compose (if applicable)
- [Node.js](https://nodejs.org/) (v14 or above, if required)
- Other dependencies (list them here if necessary)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/username/repo-name.git
   cd repo-name
   ```

2. **Set up environment variables:**
    - Copy `.env.example` to `.env`:
        ```bash
        cp .env.example .env && cd presentia-backend && cp .env.example .env 
        ```
    - Update both `.env` file with your configuration.

3. **Build the docker**:    
    ```bash
    docker-compose up --build #To run in the foreground (logs will show in the terminal):
    ```
    or
    ```bash
    docker-compose up --build -d #To run in the background (detached mode):
    ```
4. **Install PHP dependencies and set up the database:**
    - After the containers are up and running, execute the following commands in root directory:
        ```bash
        docker-compose exec php bash
        ```
    - Install PHP dependencies using Composer:
        ```bash
        composer install
        ```
    - Generate the application key:
        ```bash
        php artisan key:generate
        ```
    - Run migrations (if using a local database):
        ```bash
        php artisan migrate
        ```
        **Note:** Don't forget to add data manually or using Laravel Seeders
---

## 📂 Project Structure
The project structure should be like this
```plaintext
dockerize-presentia-backend/
├── db-data/                
│   ├── ...                 #db-data files
├── logs/   
|   ├── ...                 #logs files
├── nginx/                  #template
├── php/                    #template
├── presentia-backend/
|   ├── .env
|   ├── ...                 #rest of the folder or code
├── .env
├── .env.example  
├── .gitignore        
├── docker-compose.yml  
└── README.md           # Project documentation
```

---



If you encounter an issue, [open an issue](https://github.com/username/repo-name/issues) on GitHub.
