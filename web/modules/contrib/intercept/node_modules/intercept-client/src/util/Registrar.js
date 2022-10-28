const Registrar = class {
  constructor(name) {
    this.name = name;
    this.manifest = {};
    this.register = this.register.bind(this);
    this.getByResource = this.get.bind(this);
  }

  register(id, instance) {
    this.manifest[id] = instance;
  }

  get(id) {
    return this.manifest[id];
  }
};

export default Registrar;
