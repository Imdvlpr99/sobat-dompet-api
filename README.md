# Sobat Dompet API
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Sobat Dompet API is a native PHP RESTful API for managing wallet-related functionalities.

## Table of Contents

- [Features](#features)
- [Getting Started](#getting-started)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Usage](#usage)
- [Endpoints](#endpoints)
- [Contributing](#contributing)
- [License](#license)

## Features

- **OTP Authentication:** Secure user authentication with OTP.

## Getting Started

### Prerequisites

- PHP 7.2 or higher
- Composer installed
- MariaDB installed and running

### Installation

1. Clone the repository:

   ```
   git clone https://github.com/Imdvlpr99/sobat-dompet-api.git

2. Install dependencies:

    ```
    cd sobat-dompet-api
    composer install

## Usage

Ensure the API is up and running before using. You can use tools like Postman to interact with the API.

## Endpoints

The API endpoints are defined in the api folder. Below are the main endpoint categories:

1. User Authentication :
   -**POST /api/otp/send-otp**  : Send OTP to WhatsApp number.
   
   -**POST /api/otp/send-otp-email**  : Send OTP to Email.
   
   -**POST /api/otp/verify-otp**  : Verify OTP number.

For more detailed information on each endpoint, refer to the API Documentation.

## Contributing

Contributions are welcome! Feel free to open issues or submit pull requests.

## License

This project is licensed under the MIT License.
Adjust the instructions and links based on your project structure and documentation location. If your API documentation is inside the `api` folder, provide a link to it accordingly.
