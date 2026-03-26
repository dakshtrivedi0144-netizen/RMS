const mongoose = require('mongoose');

const moduleSchema = new mongoose.Schema({
  title: {
    type: String,
    required: true,
    trim: true
  },
  description: {
    type: String,
    required: true
  },
  resources: [{
    title: String,
    url: String,
    type: {
      type: String,
      enum: ['video', 'article', 'interactive', 'quiz', 'project'],
      default: 'article'
    },
    duration: Number, // in minutes
    completed: {
      type: Boolean,
      default: false
    }
  }],
  order: {
    type: Number,
    required: true
  },
  completed: {
    type: Boolean,
    default: false
  }
});

const learningPathSchema = new mongoose.Schema({
  title: {
    type: String,
    required: true,
    trim: true
  },
  description: {
    type: String,
    required: true
  },
  category: {
    type: String,
    required: true,
    enum: ['web-dev', 'data-science', 'ai-ml', 'cyber-security', 'mobile-dev', 'game-dev', 'other']
  },
  difficulty: {
    type: String,
    enum: ['beginner', 'intermediate', 'advanced'],
    default: 'beginner'
  },
  estimatedHours: {
    type: Number,
    default: 10
  },
  tags: [String],
  modules: [moduleSchema],
  createdBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  isPublic: {
    type: Boolean,
    default: false
  },
  likes: [{
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User'
  }],
  enrolledUsers: [{
    user: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User'
    },
    progress: {
      type: Number,
      default: 0
    },
    completed: {
      type: Boolean,
      default: false
    },
    lastAccessed: {
      type: Date,
      default: Date.now
    }
  }],
  createdAt: {
    type: Date,
    default: Date.now
  },
  updatedAt: {
    type: Date,
    default: Date.now
  }
}, {
  timestamps: true
});

// Update the updatedAt timestamp before saving
learningPathSchema.pre('save', function(next) {
  this.updatedAt = Date.now();
  next();
});

// Calculate progress for a user
learningPathSchema.methods.calculateProgress = function(userId) {
  const enrollment = this.enrolledUsers.find(e => e.user.equals(userId));
  if (!enrollment) return 0;
  
  const totalModules = this.modules.length;
  if (totalModules === 0) return 0;
  
  const completedModules = this.modules.filter(module => 
    module.resources.every(resource => resource.completed)
  ).length;
  
  return Math.round((completedModules / totalModules) * 100);
};

const LearningPath = mongoose.model('LearningPath', learningPathSchema);

module.exports = LearningPath;
