#!/bin/bash

sudo mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
sudo chown -R webapp:webapp storage
sudo chmod -R 755 bootstrap
sudo chmod -R 755 storage