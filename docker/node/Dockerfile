FROM node:20-alpine

WORKDIR /app

COPY package*.json ./

RUN if [ -f package-lock.json ]; then npm ci; else npm install --no-fund --no-audit; fi

COPY . .

EXPOSE 5173
CMD ["npm","run","dev","--","--host","0.0.0.0"]
