FROM wordpress:latest

RUN apt-get update && \
    apt-get install -y python3 python3-venv python3-pip && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/www/logs && \
    chown -R www-data:www-data /var/www && \
    chmod -R 775 /var/www

COPY scripts/python/requirements.txt /tmp/

RUN python3 -m venv /opt/venv && \
    /opt/venv/bin/pip install --upgrade pip && \
    /opt/venv/bin/pip install --no-cache-dir -r /tmp/requirements.txt

ENV PATH="/opt/venv/bin:$PATH"

COPY scripts/ /opt/scripts/
