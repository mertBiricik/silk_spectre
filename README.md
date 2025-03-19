# Silk Spectre

An interactive one-actress theatre play where the audience decides the story through live polling.

## Overview

Silk Spectre is an immersive theatrical experience where a single actress performs a dynamic narrative that evolves based on audience choices. Using their mobile devices, audience members vote on key decision points throughout the performance, collectively determining the direction of the story.

## Technical Architecture

This repository contains the web application that powers the audience polling mechanism. The application is:

- Mobile-friendly web app built with Hyperview 
- Deployed in the cloud
- Containerised using Docker
- Orchestrated with docker-compose

### Hyperview Integration

This project utilises Hyperview, a powerful XML-based UI framework that enables highly responsive and dynamic mobile interfaces with minimal network traffic. Hyperview optimises the audience polling experience by:

- Providing near-native performance on mobile browsers
- Enabling real-time updates without full page reloads
- Reducing bandwidth consumption during live performances
- Supporting offline functionality to ensure uninterrupted participation

## Getting Started

### Prerequisites

- Docker and docker-compose
- Node.js (for local development)

### Installation

1. Clone this repository:
   ```
   git clone https://github.com/yourusername/silk_spectre.git
   cd silk_spectre
   ```

2. Start the application:
   ```
   docker-compose up -d
   ```

3. Access the polling interface at `http://localhost:3000` (or your configured domain)

## Usage

### For Audience Members

1. Connect to the provided WiFi network or visit the URL provided at the venue
2. When prompted during the performance, make your choice on the polling interface
3. Watch as the story unfolds based on collective audience decisions

### For Administrators

1. Access the admin panel at `/admin` with the provided credentials
2. Create new poll questions or modify existing ones
3. Monitor live voting results
4. Trigger the display of results to the actress and audience

## Development

To run the application locally for development:

```
npm install
npm run dev
```

### Project Structure

```
silk_spectre/
├── src/
│   ├── client/        # Frontend codebase
│   ├── server/        # Backend API and server
│   └── hyperview/     # Hyperview XML templates
├── admin/             # Admin panel for managing polls
├── docker/            # Docker configuration files
├── docker-compose.yml # Container orchestration
└── README.md
```

### Testing

Run the test suite:

```
npm test
```

## Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add some amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Hyperview team for their excellent UI framework
- The theatrical community for embracing technological innovation
- All contributors and beta testers
