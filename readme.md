## composer package 自动关联yaf library插件
### 插件安装

```
	composer require yuyang/yaf-plugin:dev-master
```
### 使用注意
- 该插件仅用于yaf框架下同步composer vendor中的包,以使用yaf原生autoload
- 只支持psr-4规范下的包
- 每次composer install/update运行完毕后,会自动将更新包关联至yaf library目录
