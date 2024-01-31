## About cb-api
php8.1+laravel9+mysql+redis

## 仓库地址
https://github.com/aitradecb/cb-api

## 开发分支
https://github.com/aitradecb/cb-api/tree/develop

## 初始化项目
```bash
cp .env.develop .env
# 根据实际情况修改.env的mysql信息等
```
```bash
php artisan config:clear && php artisan migrate && php artisan db:seed
```

## 测试环境
https://www.aid.com.co/api

## CICD
提交到`develop`后会cicd到测试环境

## Apidoc
```bash
php artisan apidoc:generate
```
>[cb-api develop api doc](./build/docs/develop/index.html)

## Client
https://aid.com.co

