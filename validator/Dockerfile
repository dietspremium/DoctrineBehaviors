FROM node:18-slim

WORKDIR /app

COPY package.json .
# Install dependencies
# Note: jsonld version in schemarama 0.0.4 might be old, we try standard install first
RUN npm install

COPY . .

EXPOSE 3344

CMD ["npm", "start"]
