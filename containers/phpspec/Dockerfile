FROM minds/php:8.0

WORKDIR /var/www/Minds/engine

COPY ./containers/phpspec/phpspec.sh /var/www/Minds/phpspec.sh

ENTRYPOINT [ "sh", "../phpspec.sh" ]
CMD ["run", "--format=pretty", "--no-code-generation"]
