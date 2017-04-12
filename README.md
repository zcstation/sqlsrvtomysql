# sqlsrvtomysql
SQL Server数据库复制建表到MySQL（空表）

由于业务需要，需要在`MySQL`中建立`SQL Server`下面的表，使用`ODBJ`复制不成功就自己写了个脚本（太low了不忍直视）勉强完成任务，因为`SQL Server`和`MySQL`的数据类型不完全相同，需要进行转换，根据实际情况对以下类型就行了处理。

```
  # 主要对以下类型进行了处理
  smallint         -> tinyint
  numeric          -> int|bigint
  nvarchar|sysname -> varchar
  smalldatetime    -> datetime
  real             -> float
  image            -> varchar
```

### 这只是一个简单的脚本，功能还不够全面，还有很多问题，有好的意见或者建议欢迎联系我

@<a href="mailto:me@onezx.cn">me@onezx.cn</a>
