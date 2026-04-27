FROM node:20-alpine

WORKDIR /app

COPY services/booking-payment-service/package.json ./package.json
COPY services/booking-payment-service/src ./src

CMD ["node", "src/app.js"]
