FROM minds/php:7.3

WORKDIR /var/www/Minds/engine

COPY ./containers/phpspec/phpspec.sh /var/www/Minds/phpspec.sh

ENTRYPOINT [ "../phpspec.sh" ]
CMD ["run", "--format=pretty", "--no-code-generation"]
