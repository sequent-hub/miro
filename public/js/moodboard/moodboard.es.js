import * as PIXI from "pixi.js";
class PixiEngine {
  constructor(container, eventBus, options) {
    this.container = container;
    this.eventBus = eventBus;
    this.options = options;
    this.objects = /* @__PURE__ */ new Map();
  }
  async init() {
    this.app = new PIXI.Application({
      width: this.options.width,
      height: this.options.height,
      backgroundColor: this.options.backgroundColor,
      antialias: true
    });
    this.container.appendChild(this.app.view);
    console.log("PIXI Engine initialized");
  }
  createObject(objectData) {
    const graphics = new PIXI.Graphics();
    graphics.beginFill(16711680);
    graphics.drawRect(0, 0, objectData.width || 100, objectData.height || 100);
    graphics.endFill();
    graphics.x = objectData.position.x;
    graphics.y = objectData.position.y;
    graphics.interactive = true;
    graphics.cursor = "pointer";
    this.app.stage.addChild(graphics);
    this.objects.set(objectData.id, graphics);
    console.log("Created object:", objectData.type);
  }
  removeObject(objectId) {
    const pixiObject = this.objects.get(objectId);
    if (pixiObject) {
      this.app.stage.removeChild(pixiObject);
      this.objects.delete(objectId);
    }
  }
  destroy() {
    this.app.destroy(true);
  }
}
class StateManager {
  constructor(eventBus) {
    this.eventBus = eventBus;
    this.state = {
      board: {},
      objects: [],
      selectedObjects: [],
      isDirty: false
    };
  }
  loadBoard(boardData) {
    this.state.board = boardData;
    this.state.objects = boardData.objects || [];
    this.eventBus.emit("board:loaded", boardData);
  }
  addObject(objectData) {
    this.state.objects.push(objectData);
    this.markDirty();
    this.eventBus.emit("object:created", objectData);
  }
  removeObject(objectId) {
    this.state.objects = this.state.objects.filter((obj) => obj.id !== objectId);
    this.markDirty();
    this.eventBus.emit("object:deleted", objectId);
  }
  getObjects() {
    return [...this.state.objects];
  }
  serialize() {
    return {
      board: { ...this.state.board, objects: this.state.objects }
    };
  }
  markDirty() {
    this.state.isDirty = true;
  }
  isDirty() {
    return this.state.isDirty;
  }
}
class EventBus {
  constructor() {
    this.events = /* @__PURE__ */ new Map();
  }
  on(event, callback) {
    if (!this.events.has(event)) {
      this.events.set(event, /* @__PURE__ */ new Set());
    }
    this.events.get(event).add(callback);
  }
  off(event, callback) {
    var _a;
    (_a = this.events.get(event)) == null ? void 0 : _a.delete(callback);
  }
  emit(event, data) {
    const callbacks = this.events.get(event);
    if (callbacks) {
      callbacks.forEach((callback) => callback(data));
    }
  }
  removeAllListeners() {
    this.events.clear();
  }
}
class MoodBoard {
  constructor(container, options = {}) {
    this.container = typeof container === "string" ? document.querySelector(container) : container;
    if (!this.container) {
      throw new Error("Container not found");
    }
    this.options = {
      boardId: null,
      autoSave: false,
      width: this.container.clientWidth || 800,
      height: this.container.clientHeight || 600,
      backgroundColor: 16119285,
      ...options
    };
    this.eventBus = new EventBus();
    this.state = new StateManager(this.eventBus);
    this.pixi = new PixiEngine(this.container, this.eventBus, this.options);
    this.init();
  }
  async init() {
    try {
      await this.pixi.init();
      this.state.loadBoard({
        id: this.options.boardId || "demo",
        name: "Demo Board",
        objects: [],
        viewport: { x: 0, y: 0, zoom: 1 }
      });
      console.log("MoodBoard initialized");
    } catch (error) {
      console.error("MoodBoard init failed:", error);
    }
  }
  createObject(type, position, properties = {}) {
    const objectData = {
      id: "obj_" + Date.now(),
      type,
      position,
      width: 100,
      height: 100,
      properties,
      created: (/* @__PURE__ */ new Date()).toISOString()
    };
    this.state.addObject(objectData);
    this.pixi.createObject(objectData);
    return objectData;
  }
  deleteObject(objectId) {
    this.state.removeObject(objectId);
    this.pixi.removeObject(objectId);
  }
  get objects() {
    return this.state.getObjects();
  }
  get boardData() {
    return this.state.serialize();
  }
  destroy() {
    this.pixi.destroy();
  }
}
class ApiClient {
  constructor(baseUrl, authToken = null) {
    this.baseUrl = baseUrl;
    this.authToken = authToken;
  }
  async getBoard(boardId) {
    console.log("API: Getting board", boardId);
    return {
      data: {
        id: boardId,
        name: "Demo Board",
        objects: []
      }
    };
  }
  async saveBoard(boardId, boardData) {
    console.log("API: Saving board", boardId, boardData);
    return {
      data: boardData
    };
  }
}
const version = "1.0.0";
export {
  ApiClient,
  EventBus,
  MoodBoard,
  PixiEngine,
  StateManager,
  version
};
//# sourceMappingURL=moodboard.es.js.map
