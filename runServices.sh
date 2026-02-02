#!/bin/bash

gnome-terminal \
    --tab --title="Backend API" --command="bash -c 'cd /var/www/html/hoorstoreBackend/backend && php -S localhost:8000 -t public; $SHELL'" \
    --tab --title="Frontend" --command="bash -c 'cd /var/www/html/hoorstoreBackend/front && npm run dev; $SHELL'" \
