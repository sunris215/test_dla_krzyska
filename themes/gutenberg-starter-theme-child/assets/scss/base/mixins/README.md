# Mixins

The list of all available mixins

### Box
Code
```scss
.sample {
  @include box(50px, 100px);
}
```

Will render to

```scss
.sample {
  width: 100px;
  height: 50px;
}
```

### Center block
Code
```scss
.sample {
  @include center-block;
}
```

Will render to

```scss
.sample {
  display: block;
  margin-left: auto;
  margin-right: auto;
}
```

### Cover background
Code
```scss
.sample {
  @include cover-background;
}
```

Will render to

```scss
.sample {
  background-repeat: no-repeat;
  background-size: cover;
  background-position: center;
}
```

### Flex helpers

#### Flex column
Code
```scss
.sample {
  @include flex-column;
}
```

Will render to

```scss
.sample {
  display: flex;
  flex-direction: column;
}
```

#### Flex center
Code
```scss
.sample {
  @include flex-center;
}
```

Will render to

```scss
.sample {
  display: flex;
  align-items: center;
  justify-content: center;
}
```

#### Flex center column
Code
```scss
.sample {
    @include flex-center-column;
}
```

Will render to

```scss
.sample {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
}
```

#### Flex center vertical
Code
```scss
.sample {
    @include flex-center-vertical;
}
```

Will render to

```scss
.sample {
  display: flex;
  align-items: center;
}
```

#### Flex center horizontal
Code
```scss
.sample {
    @include flex-center-horizontal;
}
```

Will render to

```scss
.sample {
  display: flex;
  justify-content: center;
}
```

### Font size
Code
```scss
.sample {
    @include font-size(24, 700, 1.5, 0.4px);
}
```

Will render to

```scss
.sample {
  font-size: 24px;
  font-weight: 700;
  line-height: 1.5;
  letter-spacing: 0.4px;
}
```

### Media query
We have 3 options: `mobile`, `tablet`, `desktop` 

Code
```scss
.sample {
  @include mobile {
    padding: 0;
  }
}
```

Will render to

```scss
@media only screen and (max-width: 768px) {
  .sample {
    padding: 0; 
  } 
}
```

### Pseudo
Code
```scss
.sample::after {
  @include pseudo(20px, 20px);
}
```

Will render to

```scss
.sample::after {
  content: "";
  display: inline-block;
  position: absolute;
  width: 20px;
  height: 20px; 
}
```