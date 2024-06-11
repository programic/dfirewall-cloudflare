# Setup Cloudflare

## 1. Create a list
1. Go to https://dash.cloudflare.com
2. Manage account -> Configurations -> Lists
3. Create new list:
    * Identifier: public_ips
4. Go to the newly created list and grab the Account ID and List ID from the url: `https://dash.cloudflare.com/<Account ID>/configurations/lists/<List ID>`

## 2. Create a WAF rule
1. Go to https://dash.cloudflare.com
2. Websites -> Domain -> Security -> WAF
3. Create new rule:
    * Rule name: Block when the IP address is not ours
    * Action: Block
    * Expression: 
        ```
        (http.host eq "sub.domain.com" and not ip.src in $public_ips)
        ```

# Docker

## 1. Prerequisites
1. Copy `docker-compose.example.yml` to `docker-compose.yml`
3. Edit `docker-compose.yml` and fill in the Account ID and List ID from the step above. For the Auth Email and Auth Key, go to https://dash.cloudflare.com/profile/api-tokens and grab the Global API Key.

## 2. Build (optional)
```bash 
docker compose build
docker compose push
```

## 3. Deploy
```bash 
docker compose up -d
```

# Run job manually
```bash
docker compose exec job php job.php
```