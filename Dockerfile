FROM php:7.4-cli
WORKDIR /app
COPY index.php .
EXPOSE 80
CMD [ "php", "-S", "0.0.0.0:80", "./index.php" ]