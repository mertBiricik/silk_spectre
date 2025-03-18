# silk_spectre
# Silk Spectre

An interactive one-actress theatre play where the audience decides the story through live polling.

## Overview

Silk Spectre is an immersive theatrical experience where a single actress performs a dynamic narrative that evolves based on audience choices. Using their mobile devices, audience members vote on key decision points throughout the performance, collectively determining the direction of the story.

## Technical Architecture

This repository contains the web application that powers the audience polling mechanism. The application is:

- Mobile-friendly web app
- Deployed in the cloud
- Containerised using Docker
- Orchestrated with docker-compose

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
