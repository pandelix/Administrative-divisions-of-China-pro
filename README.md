### 此项目的基础数据是来自于

https://github.com/modood/Administrative-divisions-of-China

## 再次感谢他！帮我点赞的同时也不要忘记给他点一个赞

### 本项目更新时间：2023-03-14

### 此项目所做的事情：

https://github.com/modood/Administrative-divisions-of-China/blob/master/dist/pca-code.json
将这份json数据的树状结构数据扁平化存储到mysql中。
记录了级别（省=1、市=2、区=3），并记录了各级code，生成了合并名称

然后对各级名称进行了一些简化，并生成简化后的合并名称

### 简化结果mysql文件直接下载:

https://github.com/pandelix/Administrative-divisions-of-China-pro/blob/master/areas.sql

### 简化规则如下：

- 省一级的"省"字都统一去掉了
- 去掉了“自治”字样
- 去掉了各个名族的字样
- 高新技术产业开发区、高技术产业开发区  --> 高新区
- 经济技术开发区  -->  经开区
- 现代xx园区 去掉了“现代”
- 城乡一体化示范区   --> 示范区
- 转型综合改革示范区  --> 综改区
- 文化旅游创意园区 --> 文创园
- 郑州航空港经济综合实验区 -->郑州空港经济区
- 吉林中国新加坡食品区-->吉林食品区
- 石家庄循环化工园区-->石家庄化工园区
- 某某市管理区 去掉了“某某市”

### 查看有效的简化

```mysql
SELECT * FROM `areas` WHERE `name` <> short_name AND `level` = 3
```
