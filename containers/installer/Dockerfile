FROM minds/php:latest

# Install the necessary package dependencies.
RUN apk add git npm

COPY containers/installer/install.sh install.sh

ENTRYPOINT [ "sh", "./install.sh" ]

