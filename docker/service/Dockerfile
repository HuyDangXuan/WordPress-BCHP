FROM node:20-alpine

WORKDIR /app

COPY services/booking-payment-service/package*.json ./
RUN npm install --omit=dev
COPY services/booking-payment-service/src ./src

CMD ["node", "src/app.js"]
