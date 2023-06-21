FROM minds/php:8.1

WORKDIR /var/www/Minds/engine

COPY ./containers/phpspec/phpspec.sh /var/www/Minds/phpspec.sh

ENTRYPOINT [ "sh", "../phpspec.sh" ]
CMD ["run", "--format=pretty", "--no-code-generation"]
