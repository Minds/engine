FROM minds/php:8.1

RUN apk update && apk add --no-cache --update git

COPY containers/installer/install.sh install.sh

ENTRYPOINT [ "sh", "./install.sh" ]
