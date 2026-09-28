FROM php:8.1-cli
 
# Instala extensões necessárias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_mysql sockets \
    && rm -rf /var/lib/apt/lists/*
 
# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
 
# Configura bash colorido
RUN echo 'export LS_OPTIONS="--color=auto"' >> /etc/bash.bashrc && \
    echo 'export CLICOLOR_FORCE=1' >> /etc/bash.bashrc && \
    echo 'alias ls="ls $LS_OPTIONS"' >> /etc/bash.bashrc && \
    echo 'alias ll="ls $LS_OPTIONS -lh"' >> /etc/bash.bashrc && \
    echo 'PS1="\[\033[01;32m\]\u@\h\[\033[00m\]:\[\033[01;34m\]\w\[\033[00m\]\$ "' >> /etc/bash.bashrc

WORKDIR /app
 
CMD ["/bin/bash"]
